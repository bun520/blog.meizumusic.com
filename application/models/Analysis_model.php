<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/ModelBase.php';

/**
 * 活动项目表单数据处理类
 *
 * @author: zhaodechang@wesai.com
 **/
class Analysis_model extends ModelBase
{

	/**
	 * init
	 *
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function get_db()
	{
		return CONTEST_DB_CONFIG;
	}

	public function getContestCountPerDay($fkCorp, $date)
	{
		$sql = 'select count(*) as cnt from ' . $this->tableNameContest . ' where fk_corp = :fkCorp and publish_state > :state and ctime > :fromTime and ctime <= :toTime';

		$state    = CONTEST_PUBLISH_STATE_DRAFT;
		$fromTime = date('Y-m-d 00:00:00', strtotime($date));
		$toTime   = date('Y-m-d 23:59:59', strtotime($date));

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('fkCorp', 'state', 'fromTime', 'toTime'));
	}

	public function checkCalcDataDuplicated($fkCorp, $date)
	{
		$params = compact('fkCorp', 'date');

		$sql = 'select * from ' . $this->tableNameAnalysisContest . ' where fk_corp = :fkCorp and date = :date';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params);
	}

	public function setCalcData($fkCorp, $date, $params)
	{
		$verify = $this->checkCalcDataDuplicated($fkCorp, $date);
		if (empty($verify)) {
			$params['fk_corp'] = $fkCorp;
			$params['date']    = $date;

			return $this->mixedInsertData($this->tableNameAnalysisContest, $params);
		}

		$conditions = array(
			'fk_corp' => $fkCorp,
			'date'    => $date,
		);

		return $this->mixedUpdateData($this->tableNameAnalysisContest, $params, $conditions);
	}

	public function getAnalysisContestByUnqKey($fkCorp, $date)
	{
		$sql = 'select * from ' . $this->tableNameAnalysisContest . ' where fk_corp = :fkCorp and date = :date';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('fkCorp', 'date'));
	}

	public function getAnalysisCursor($name)
	{
		$sql = 'select * from ' . $this->tableNameAnalysisCursor . ' where name = :name ';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('name'));
	}

	public function setAnalysisCursor($name, $value)
	{
		$params          = compact('name', 'value');
		$params['utime'] = null;

		$exceptKeys = array('utime');

		return $this->mixedInsertData($this->tableNameAnalysisCursor, $params, $exceptKeys);
	}

	public function listOrderPerDay($fkCorp, $date, $pageNumber, $pageSize)
	{
		$sql = 'select pk_order, fk_corp, fk_contest, fk_contest_items, fk_component_authorizer_app, fk_user, amount  from ' . $this->tableNameOrder . ' where fk_corp = :fkCorp and state > :state and ctime > :fromTime and ctime <= :toTime order by pk_order asc ';

		$state    = ORDER_STATE_FAILED;
		$fromTime = date('Y-m-d 00:00:00', strtotime($date));
		$toTime   = date('Y-m-d 23:59:59', strtotime($date));

		$params = compact('fkCorp', 'state', 'fromTime', 'toTime');

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params, $pageNumber, $pageSize);
	}

	public function setOrderCount($fkCorp, $date, $cid, $itemId, $orderCount, $amountSum)
	{
		$params = array(
			'fk_corp'          => $fkCorp,
			'date'             => $date,
			'fk_contest'       => $cid,
			'fk_contest_items' => $itemId,
			'order_count'      => $orderCount,
			'amount_sum'       => $amountSum,
		);

		$exceptKeys = array('contest_count', 'amount_sum');

		return $this->mixedInsertData($this->tableNameAnalysisOrder, $params, $exceptKeys);
	}

	public function listContestItemPerDay($fkCorp, $date, $pageNumber, $pageSize)
	{
		$sql = 'select pk_contest_items, fk_corp, fk_contest  from ' . $this->tableNameContestItem . ' where fk_corp = :fkCorp and state = :state and ctime > :fromTime and ctime <= :toTime order by pk_contest_items asc ';

		$state    = CONTEST_ITEM_STATE_OK;
		$fromTime = date('Y-m-d 00:00:00', strtotime($date));
		$toTime   = date('Y-m-d 23:59:59', strtotime($date));

		$params = compact('fkCorp', 'state', 'fromTime', 'toTime');

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params, $pageNumber, $pageSize);
	}

	public function setContestItemCount($fkCorp, $date, $cid, $itemCount)
	{
		$params = array(
			'fk_corp'    => $fkCorp,
			'date'       => $date,
			'fk_contest' => $cid,
			'item_count' => $itemCount,
		);

		$exceptKeys = array('item_count');

		return $this->mixedInsertData($this->tableNameAnalysisContestItem, $params, $exceptKeys);
	}

	public function getContestItemCalcPerDay($fkCorp, $date)
	{
		$sql = 'select sum(item_count) as item_count from ' . $this->tableNameAnalysisContestItem . ' where fk_corp = :fkCorp and date = :date';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('fkCorp', 'date'));
	}

	public function getOrderCalcPerDay($fkCorp, $date)
	{
		$sql = 'select sum(order_count) as order_count, sum(amount_sum) as amount_sum from ' . $this->tableNameAnalysisOrder . ' where fk_corp = :fkCorp and date = :date';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('fkCorp', 'date'));
	}

	public function getSumNumber($fkCorp, $days = null)
	{
		$sql = 'select sum(contest_count) as contest_count, sum(item_count) as item_count, sum(order_count) as order_count, sum(amount_sum) as amount_sum from ' . $this->tableNameAnalysisContest . ' where fk_corp = :fkCorp';

		$params = compact('fkCorp');

		if (!empty($days)) {
			$fromDate = date('Y-m-d', strtotime('-' . ($days + 1) . ' days'));
			$toDate   = date('Y-m-d', strtotime('-1 days'));

			$sql .= ' and date > :fromDate and date <= :toDate';

			$params['fromDate'] = $fromDate;
			$params['toDate']   = $toDate;
		}

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params);
	}

	public function listDaily($fkCorp, $pageNumber, $pageSize, &$total)
	{
		$params    = compact('fkCorp');
		$sqlSuffix = ' from ' . $this->tableNameAnalysisContest . ' where fk_corp = :fkCorp ';

		$sql = 'select count(*) as cnt ' . $sqlSuffix;

		$count = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params);
		$total = $count['cnt'];

		$sql = 'select pk_analysis_contest, date, contest_count, item_count, order_count, amount_sum '
		       . $sqlSuffix . ' order by date desc ';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params, $pageNumber, $pageSize);
	}
} // END class Msg_model
