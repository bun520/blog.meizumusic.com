<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/ModelBase.php';

/**
 * 活动活动项目订单类
 *
 * @author: zhaodechang@wesai.com
 **/
class Order_model extends ModelBase
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

	public function addOrder(
		$fk_user, $fk_contest, $fk_contest_items, $amount, $ip, $enrolInfo, $shipping_addr,
		$order_source, $fk_corp, $fk_component_authorizer_app, $channel_account, $max_verify
	) {
		try {
			$std          = new stdClass();
			$std->orderId = '';
			$std->error   = 0;

			// 检查库存
			$query = "SELECT max_stock, cur_stock FROM " . $this->tableNameContestItem . "
                      WHERE pk_contest_items = :fk_contest_items";

			$itemInfo = $this->getSingle(Pdo_Mysql::DSN_TYPE_MASTER, $query, compact('fk_contest_items'));
			if (empty($itemInfo)) {
				$std->error = -1;

				return $std;
			}

			if ($itemInfo['max_stock'] > 0 && $itemInfo['cur_stock'] <= 0) {
				$std->error = -2;

				return $std;
			}

			$this->beginTransaction();

			// 有报名人数上限
			if ($itemInfo['max_stock'] > 0) {
				// 减库存
				$query = 'UPDATE ' . $this->tableNameContestItem . ' SET cur_stock = cur_stock - 1
			             WHERE pk_contest_items = :fk_contest_items AND cur_stock > 0';

				$affectedRows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query, compact('fk_contest_items'));
				if (empty($affectedRows)) {
					$this->rollBack();

					$std->error = -3;

					return $std;
				}
			}

			// 创建订单数据
			$utime           = null;
			$orderBindParams = compact(
				'fk_user', 'fk_contest', 'fk_contest_items', 'amount', 'ip', 'utime',
				'shipping_addr', 'order_source', 'fk_corp', 'fk_component_authorizer_app',
				'channel_account', 'max_verify'
			);

			$exceptKeys = array('utime', 'shipping_addr', 'ip', 'max_verify');

			$orderSqlData = $this->makeInsertSqlData($this->tableNameOrder, $orderBindParams, $exceptKeys);

			$orderId = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $orderSqlData['sql'], $orderSqlData['bindParams']);
			if (empty($orderId)) {
				$this->rollBack();
				$std->error = -4;

				return $std;
			}

			// 返回订单ID
			$std->orderId = $orderId;

			//报名详情入库
			$enrolInfoSqlData = array();
			foreach ($enrolInfo as $k => $v) {
				$v['fk_order'] = $orderId;

				$enrolInfoSqlData[] = $this->makeInsertSqlData($this->tableNameEnrolInfo, $v, [], $k);
			}

			if (empty($enrolInfoSqlData)) {
				$this->rollBack();
				$std->error = -5;

				return $std;
			}

			$enrolInfoSql        = explode('values', $enrolInfoSqlData[0]['sql'])[0] . ' values ';
			$enrolInfoBindParams = array();
			$strSqlValues        = array();
			foreach ($enrolInfoSqlData as $k => $v) {
				$strSqlValues[]      = explode('values', $v['sql'])[1];
				$enrolInfoBindParams = array_merge($enrolInfoBindParams, $v['bindParams']);
			}

			$enrolInfoSql .= implode(',', $strSqlValues) . ';';

			$result = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $enrolInfoSql, $enrolInfoBindParams);

			if (empty($result)) {
				$this->rollBack();
				$std->error = -6;

				return $std;
			}

			$this->commit();

			return $std;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 从外部订单号中获取内部订单号
	 *
	 * @param  string $out_trade_no 外部订单号
	 *
	 * @return int
	 */
	public function getOrderIdFromOutTradeNo($out_trade_no)
	{
		return intval(substr($out_trade_no, 9));
	}

	public function changeStateToPaying($pk_order)
	{
		try {
			$this->beginTransaction();
			$out_trade_no = $this->createOutTradeNo($pk_order);

			$params = array(
				'state'        => ORDER_STATE_PAYING,
				'out_trade_no' => $out_trade_no,
			);

			$conditions = array(
				'pk_order' => $pk_order,
				'state'    => ORDER_STATE_INIT,
			);

			$sqlData = $this->makeUpdateSqlData($this->tableNameOrder, $params, $conditions);

			$affected_rows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);

			if ($affected_rows != 1) {
				$this->rollBack();

				return false;
			}

			$params = array(
				'fk_order'   => $pk_order,
				'from_state' => ORDER_STATE_INIT,
				'to_state'   => ORDER_STATE_PAYING,
				'remark'     => __METHOD__,
			);

			$sqlData = $this->makeInsertSqlData($this->tableNameOrderStateLog, $params);

			$this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);

			$this->commit();

			return $out_trade_no;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 生成外部订单号 2016030110000000001
	 *
	 * @param  integer $orderid 订单ID
	 *
	 * @return string
	 */
	public function createOutTradeNo($orderid)
	{
		return date('Ymd') . '1' . sprintf('%010d', $orderid);
	}

	public function changeStateToFailed($pk_order, $fromState)
	{
		return $this->changeState($pk_order, $fromState, ORDER_STATE_FAILED, __METHOD__);
	}

	private function changeState($pk_order, $from_state, $to_state, $remark = null)
	{
		try {
			$this->beginTransaction();

			$query = 'UPDATE ' . $this->tableNameOrder . ' SET state = :to_state WHERE pk_order = :pk_order AND state = :from_state';

			$params               = array();
			$params['to_state']   = $to_state;
			$params['from_state'] = $from_state;
			$params['pk_order']   = $pk_order;

			$affected_rows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);

			if ($affected_rows != 1) {
				$this->rollBack();

				return false;
			}

			$query = 'INSERT INTO ' . $this->tableNameOrderStateLog . ' (fk_order, from_state, to_state, remark)
                      VALUES (:pk_order, :from_state, :to_state, :remark)';

			$params['remark'] = $remark;

			$lastid = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);

			$this->commit();

			return $affected_rows;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	public function changeStateToCompleted($orderId, $paidTime, $channelId, $transactionId)
	{
		try {
			$this->beginTransaction();

			$params = array(
				'state'                  => ORDER_STATE_COMPLETED,
				'paid_time'              => empty($paidTime) ? date('Y-m-d H:i:s') : $paidTime,
				'lottery_state'          => ORDER_LOTTERY_STATE_START,
				'channel_id'             => $channelId,
				'channel_transaction_id' => $transactionId,
			);

			$conditions = array(
				'pk_order' => $orderId,
				'state'    => ORDER_STATE_PAYING,
			);

			$sqlData = $this->makeUpdateSqlData($this->tableNameOrder, $params, $conditions);
			if (empty($sqlData)) {
				$this->rollBack();

				return false;
			}

			$affectedRows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);

			if ($affectedRows != 1) {
				$this->rollBack();

				$errMsg = array(
					'msg'        => 'update order state failed',
					'params'     => $params,
					'conditions' => $conditions,
				);
				log_message_v2('error', $errMsg);

				return false;
			}

			$params = array(
				'fk_order'   => $orderId,
				'from_state' => ORDER_STATE_PAYING,
				'to_state'   => ORDER_STATE_COMPLETED,
				'remark'     => __METHOD__,
			);

			$sqlData = $this->makeInsertSqlData($this->tableNameOrderStateLog, $params);
			if (empty($sqlData)) {
				$this->rollBack();

				return false;
			}

			$orderLogId = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);
			if (empty($orderLogId)) {
				$errMsg = array(
					'msg'    => 'write order state log failed',
					'params' => $params,
				);
				log_message_v2('error', $errMsg);
			}

			$this->commit();

			return $affectedRows;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	public function changeStateToClosed($pk_order)
	{
		try {
			$this->beginTransaction();

			$query                   = 'UPDATE ' . $this->tableNameOrder . ' SET state = :to_state, lottery_state = :lottery_state 
					  WHERE pk_order = :pk_order AND state = :from_state';
			$params                  = array();
			$params['to_state']      = ORDER_STATE_CLOSED;
			$params['from_state']    = ORDER_STATE_COMPLETED;
			$params['lottery_state'] = ORDER_LOTTERY_STATE_SUCCESS;
			$params['pk_order']      = $pk_order;

			$affected_rows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);

			if ($affected_rows != 1) {
				$this->rollBack();

				return false;
			}

			$query = 'INSERT INTO ' . $this->tableNameOrderStateLog . ' (fk_order, from_state, to_state, remark)
                      VALUES (:pk_order, :from_state, :to_state, :remark)';

			$params               = array();
			$params['to_state']   = ORDER_STATE_CLOSED;
			$params['from_state'] = ORDER_STATE_COMPLETED;
			$params['pk_order']   = $pk_order;
			$params['remark']     = __METHOD__;

			$orderStateLogId = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);
			if (empty($orderStateLogId)) {
				$errMsg = array(
					'msg'     => __METHOD__ . ' write ' . $this->tableNameOrderStateLog . ' failed',
					'orderId' => $pk_order,
				);
				log_message_v2('error', $errMsg);
			}

			$this->commit();

			return $affected_rows;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 更新订单状态到“退款中”
	 *
	 * @param  integer $pk_order 订单ID
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function changeStateToRefunding($pk_order)
	{
		try {
			$this->beginTransaction();
			$out_refund_no = $this->createOutRefundNo($pk_order);

			$query = 'UPDATE ' . $this->tableNameOrder . ' SET state = :to_state, out_refund_no = :out_refund_no,
                      lottery_state = :lottery_state
                      WHERE pk_order = :pk_order AND state = :from_state';

			$params                  = array();
			$params['to_state']      = ORDER_STATE_REFUNDING;
			$params['from_state']    = ORDER_STATE_COMPLETED;
			$params['lottery_state'] = ORDER_LOTTERY_STATE_FAILED;
			$params['pk_order']      = $pk_order;
			$params['out_refund_no'] = $out_refund_no;

			$affected_rows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);

			if ($affected_rows != 1) {
				$this->rollBack();

				return false;
			}

			$query = 'INSERT INTO ' . $this->tableNameOrderStateLog . ' (fk_order, from_state, to_state, remark)
                      VALUES (:pk_order, :from_state, :to_state, :remark)';

			$params               = array();
			$params['to_state']   = ORDER_STATE_REFUNDING;
			$params['from_state'] = ORDER_STATE_COMPLETED;
			$params['pk_order']   = $pk_order;
			$params['remark']     = __METHOD__;

			$lastid = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);

			$this->commit();

			return $out_refund_no;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 生成外部退款单号
	 *
	 * @param  integer $orderid 订单ID
	 *
	 * @return string
	 */
	public function createOutRefundNo($orderid)
	{
		return date('Ymd') . '2' . sprintf('%010d', $orderid);
	}

	public function changeStateToRefundFailed($pk_order)
	{
		return $this->changeState($pk_order, ORDER_STATE_REFUNDING, ORDER_STATE_REFUND_FAILED, __METHOD__);
	}

	public function changeStateToRefundCompleted($pk_order)
	{
		return $this->changeState($pk_order, ORDER_STATE_REFUNDING, ORDER_STATE_REFUND_COMPLETED, __METHOD__);
	}

	/**
	 * 获取指定用户的订单列表
	 *
	 * @param          $fk_corp
	 * @param          $fk_component_authorizer_app
	 * @param  string  $fk_user 用户ID
	 * @param  integer $state   订单状态
	 * @param          $cid
	 * @param  integer $page    页码
	 * @param  integer $size    页长
	 *
	 * @return \stdClass
	 */
	public function listUserOrder($fk_corp, $fk_component_authorizer_app, $fk_user, $state, $cid, $page = 1, $size = 10)
	{
		$std        = new stdClass();
		$std->total = 0;
		$std->data  = array();

		$params   = compact('fk_user', 'fk_corp', 'fk_component_authorizer_app');
		$whereStr = ' where fk_corp = :fk_corp and fk_component_authorizer_app = :fk_component_authorizer_app and fk_user = :fk_user ';

		if (!empty($state) && empty($cid)) {
			$params['state'] = $state;
			$whereStr .= '  and state = :state ';
		}

		if (!empty($cid)) {
			$params['cid'] = $cid;
			$orderValid    = array(
				ORDER_STATE_PAYING,
				ORDER_STATE_COMPLETED,
				ORDER_STATE_CLOSED,
				ORDER_STATE_REFUNDING,
				ORDER_STATE_REFUND_COMPLETED,
				ORDER_STATE_REFUND_FAILED,
			);
			$tmpStr        = ' and fk_contest = :cid and state in (%s) ';
			$tmpStr        = sprintf($tmpStr, implode(',', $orderValid));

			$whereStr .= $tmpStr;
		}

		$query = 'SELECT count(pk_order) AS cnt FROM ' . $this->tableNameOrder . $whereStr;

		$t = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, $params);
		if (!empty($t) && array_key_exists('cnt', $t)) {
			$std->total = $t['cnt'];
		}
		if (empty($std->total)) {
			return $std;
		}

		$query = 'SELECT pk_order, fk_user, fk_contest, fk_contest_items, state,
                      channel_id, amount, ip, ctime, paid_time
                      FROM  ' . $this->tableNameOrder . $whereStr . ' ORDER BY ctime DESC';

		$std->data = $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, $params, $page, $size);

		return $std;
	}

	public function searchOrderManage($params)
	{
		try {
			$configName = SPHINX_INDEX_ORDER_MANAGE;
			$sphConfig  = $this->config->item('sphinx')[$configName];
			$this->load->helper('sphinx');
			$sphinxClient = sphinx_init_helper($sphConfig);
			$sphinxClient->SetSortMode(SPH_SORT_EXTENDED, 'ctime DESC');
			$sphinxClient->SetLimits(($params['page'] - 1) * $params['size'], $params['size'], $sphConfig['max_matched']);

			$fk_corp = $params['fk_corp'];
			$sphinxClient->SetFilter('fk_corp', compact('fk_corp'));

			$query = '';
			if (!empty($params['cname'])) {
				$query .= '@cname ' . $params['cname'] . ' ';
			}

			if (!empty($params['citems'])) {
				$query .= '@iname ' . $params['citems'] . ' ';
			}

			if (!empty($params['idno'])) {
				$query .= '@idno ' . $params['idno'];
			}

			if (!empty($params['mobile'])) {
				$query .= '@mobile ' . $params['mobile'];
			}

			if (!empty($params['channel_transaction_id'])) {
				$query .= '@channel_transaction_id ' . $params['channel_transaction_id'];
			}

			if (!empty($params['lottery_state'])) {
				$sphinxClient->SetFilter('lottery_state', array($params['lottery_state']));
			}

			if (!empty($params['deliver_gear'])) {
				$sphinxClient->SetFilter('deliver_gear', array($params['deliver_gear']));
			}

			if (!empty($params['state'])) {
				$sphinxClient->SetFilter('state', array($params['state']));
			}

			if (!empty($params['start']) && !empty($params['end'])) {
				$sphinxClient->SetFilterRange('ctime', $params['start'], $params['end']);
			}

			$result = $sphinxClient->Query($query, $sphConfig['index']);
			$ids    = array();
			if (!empty($result['matches'])) {
				foreach ($result['matches'] as $key => $value) {
					$ids[] = $value['id'];
				}
			}

			$std         = new stdClass();
			$std->total  = intval($result['total']);
			$std->result = $ids;

			return $std;
		} catch (Exception $e) {
			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 根据订单ID获取订单详情
	 *
	 * @param  integer $pk_order 订单ID
	 * @param string   $dsn_type 数据库连接方式
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getOrderById($pk_order, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'SELECT * FROM ' . $this->tableNameOrder . ' WHERE pk_order = :pk_order';

		return $this->getSingle($dsn_type, $query, compact('pk_order'));
	}

	public function getByIds($orderIds)
	{
		$size = count($orderIds);

		$orderIds = implode(',', $orderIds);

		$sql = 'select * from ' . $this->tableNameOrder . ' where pk_order in (%s)';

		$sql = sprintf($sql, $orderIds);

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, array(), 1, $size);
	}

	/**
	 * 获取报名详情
	 *
	 * @param  integer $oid 订单ID
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function listEnrolInfo($oid)
	{
		$query = 'SELECT * FROM t_enrol_info WHERE fk_order = :oid';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('oid'));
	}

	public function getSpecifiedEnrolInfo($oid, $key)
	{
		$query = 'SELECT value FROM t_enrol_info WHERE fk_order = :oid AND type = :type';

		$type = $key;

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('oid', 'type'));
	}

	public function listOrderExpired($pageNumber, $pageSize)
	{
		$query = 'SELECT pk_order FROM ' . $this->tableNameOrder . ' WHERE state in (:stateInit, :statePaying) AND ctime < :time';

		$stateInit   = ORDER_STATE_INIT;
		$statePaying = ORDER_STATE_PAYING;
		$time        = date('Y-m-d H:i:s', strtotime("-" . ORDER_PAY_TIME_LIMIT . " minutes"));

		return $this->getAll(Pdo_Mysql::DSN_TYPE_MASTER, $query, compact('stateInit', 'statePaying', 'time'), $pageNumber, $pageSize);
	}

	public function updateEnrolValue($pk_enrol_info, $value)
	{
		$params     = compact('value');
		$conditions = compact('pk_enrol_info');

		return $this->mixedUpdateData('t_enrol_info', $params, $conditions);
	}

	/**
	 * 根据活动ID 获取报名成功订单
	 *
	 * @param  integer $cid        活动ID
	 * @param  integer $pageNumber 页码
	 * @param  integer $pageSize   页长
	 * @param int      $state
	 * @param int      $lottery_state
	 *
	 * @return array|bool
	 */
	public function listRegSuccessOrder($cid, $pageNumber, $pageSize, $state = 0, $lottery_state = 0)
	{
		$params   = compact('cid');
		$whereStr = ' WHERE fk_contest = :cid ';

		if (!empty($state)) {
			$params['state'] = $state;
			$whereStr .= '  AND state = :state ';
		}

		if (!empty($lottery_state)) {
			$params['lottery_state'] = $lottery_state;
			$whereStr .= '  AND lottery_state = :lottery_state ';
		}

		$query = 'SELECT pk_order, fk_contest, fk_contest_items,fk_user, channel_id, out_trade_no, channel_transaction_id, amount,
                  state, lottery_state, out_refund_no, shipping_addr, ctime, fk_corp, fk_component_authorizer_app
                  FROM  ' . $this->tableNameOrder . $whereStr . ' ORDER BY ctime ASC';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, $params, $pageNumber, $pageSize);
	}

	public function listOrder($pageNumber, $pageSize)
	{
		$query = 'select pk_order, fk_user, fk_contest, fk_contest_items,
                  state, ctime,channel_account, channel_transaction_id, lottery_state,
                  order_source, fk_corp, fk_component_authorizer_app
                  from  ' . $this->tableNameOrder;

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, array(), $pageNumber, $pageSize);
	}

	public function getYesterdayOrder($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$ytime = time() - 24 * 60 * 60;
		$atime = date('Y-m-d 00:00:00', $ytime);
		$ztime = date('Y-m-d 23:59:59', $ytime);
		$query = 'SELECT count(pk_order)  FROM ' . $this->tableNameOrder . ' WHERE fk_corp = :fk_corp AND  ctime > :atime AND ctime < :ztime  ';

		return $this->getSingle($dsn_type, $query, compact('atime', 'ztime', 'fk_corp'));
	}

	public function getTodayOrder($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$ytime = time();
		$atime = date('Y-m-d 00:00:00', $ytime);
		$ztime = date('Y-m-d 23:59:59', $ytime);
		$query = 'SELECT count(pk_order)  FROM ' . $this->tableNameOrder . ' WHERE fk_corp = :fk_corp AND  ctime > :atime AND ctime < :ztime  ';

		return $this->getSingle($dsn_type, $query, compact('atime', 'ztime', 'fk_corp'));
	}

	public function getCurrentOrder($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'SELECT count(pk_order)  FROM ' . $this->tableNameOrder . ' WHERE fk_corp = :fk_corp';

		return $this->getSingle($dsn_type, $query, compact('fk_corp'));
	}

	public function VerifyOrder($orderId, $pkCorp, $userId, $verifyNumber)
	{
		try {
			$this->beginTransaction();

			$result = $this->increaseVerifyNumber($orderId);
			if (empty($result)) {
				$this->rollBack();

				return false;
			}

			$result = $this->writeOrderVerifyLog($orderId, $pkCorp, $userId, $verifyNumber, $verifyNumber + 1);
			if (empty($result)) {
				$this->rollBack();

				return false;
			}

			$this->commit();


			return 1;
		} catch (Exception $e) {
			$this->rollBack();
			$this->logException($e);

			return false;
		}
	}

	private function increaseVerifyNumber($orderId)
	{
		$conditions = array(
			'pk_order' => $orderId,
			'state'    => ORDER_STATE_CLOSED,
		);

		$sql = 'update ' . $this->tableNameOrder . ' set verify_number = verify_number + 1 where pk_order = :pk_order and state = :state';

		return $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sql, $conditions);
	}

	private function writeOrderVerifyLog($orderId, $pkCorp, $userId, $fromNumber, $toNumber)
	{
		$params = array(
			'fk_order'       => $orderId,
			'fk_corp'        => $pkCorp,
			'verify_user_id' => $userId,
			'from_number'    => $fromNumber,
			'to_number'      => $toNumber,
		);

		$exceptKeys = array('from_number');

		return $this->mixedInsertData($this->tableNameOrderVerifyLog, $params, $exceptKeys);
	}

	public function getItemOrderCount($fkCorp, $contestId, $itemId, $isVerified = false)
	{
		$sql = 'select count(*) as cnt from ' . $this->tableNameOrder . ' where fk_corp = :fkCorp and fk_contest = :contestId and fk_contest_items = :itemId and state = :state';

		$state  = ORDER_STATE_CLOSED;
		$params = compact('fkCorp', 'contestId', 'itemId', 'state');
		if ($isVerified) {
			$sql .= ' and verify_number > 0';
		}

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params);
	}
}
// END class Msg_model
