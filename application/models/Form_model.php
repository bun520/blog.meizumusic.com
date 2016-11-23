<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/ModelBase.php';

/**
 * 活动项目表单数据处理类
 *
 * @author: zhaodechang@wesai.com
 **/
class Form_model extends ModelBase
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


	/**
	 * 新增报名表单
	 *
	 * @param $itemId
	 * @param $name
	 *
	 * @return mixed
	 *
	 */
	public function addForm($itemId, $name)
	{
		$params = array(
			'fk_contest_items' => $itemId,
			'name'             => $name,
			'utime'            => null,
		);

		return $this->mixedInsertData('t_enrol_form', $params, ['utime']);
	}

	/**
	 * 根据表单ID
	 *
	 * @param  integer $pk_enrol_form 表单ID
	 * @param string   $dsn_type      数据库连接方式，默认从库
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getFormById($pk_enrol_form, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'select pk_enrol_form, fk_contest_items, name, ctime, utime from t_enrol_form where pk_enrol_form = :pk_enrol_form';

		return $this->getSingle($dsn_type, $query, compact('pk_enrol_form'));
	}

	/**
	 * 根据活动项目ID获取报名表单资料
	 *
	 * @param  integer $fk_contest_items 活动项目ID
	 * @param string   $dsn_type         数据库连接方式，默认从库
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getFormByItemId($fk_contest_items, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'select pk_enrol_form, fk_contest_items, name, ctime, utime from t_enrol_form where fk_contest_items = :fk_contest_items';

		return $this->getSingle($dsn_type, $query, compact('fk_contest_items'));
	}

	public function getFormByItemIds($itemIds)
	{
		$sql        = 'select * from t_enrol_form where fk_contest_items in (%s)';
		$strItemIds = implode(',', $itemIds);
		$sql        = sprintf($sql, $strItemIds);

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, [], 1, count($itemIds));
	}

	/**
	 * 更新报名表单
	 *
	 * @param  integer $pk_enrol_form 表单ID
	 * @param  string  $name          表单名称
	 *
	 * @return mixed
	 */
	public function updateForm($pk_enrol_form, $name = null)
	{
		$params = array(
			'utime' => null,
		);
		if (!empty($name)) {
			$params['name'] = $name;
		}

		$conditions = compact('pk_enrol_form');

		return $this->mixedUpdateData('t_enrol_form', $params, $conditions);
	}

	public function addFormItem($params)
	{
		return $this->mixedInsertData('t_enrol_form_item', $params);
	}

	public function updateFormItem($params, $conditions)
	{
		return $this->mixedUpdateData('t_enrol_form_item', $params, $conditions);
	}

	public function deleteFormItem($pkFormItem)
	{
		$sql = 'update t_enrol_form_item set state = :toState where pk_enrol_form_item = :pkFormItem and state = :fromState';

		$toState   = ENROL_FORM_ITEM_STATE_NG;
		$fromState = ENROL_FORM_ITEM_STATE_OK;

		return $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sql, compact('pkFormItem', 'toState', 'fromState'));
	}

	public function listFormItemByFormId($formId, $pageNumber, $pageSize)
	{
		$sql   = 'select * from t_enrol_form_item where fk_enrol_form = :formId and state = :state ORDER BY seq ASC ';
		$state = ENROL_FORM_ITEM_STATE_OK;

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('formId', 'state'), $pageNumber, $pageSize);
	}

	public function updateFormItemSeqs($params)
	{
		if (empty($params)) {
			return false;
		}

		$arrSql = array();
		foreach ($params as $k => $v) {
			$arrSql[] = 'update t_enrol_form_item set seq = ' . intval($v['seq']) .
			            ' where pk_enrol_form_item = ' . intval($v['pk_enrol_form_item']) . ';';
		}

		$strSql = implode(' ', $arrSql);

		return $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $strSql, array());
	}

	public function getFormItemMaxSeq($formId)
	{
		$sql = 'select max(seq) as seq  from  t_enrol_form_item WHERE fk_enrol_form = :formId';

		$result = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('formId'));
		$result = intval($result);

		return ($result + 1);
	}

	public function getFormItemById($formItemId)
	{
		$sql = 'select * from t_enrol_form_item where pk_enrol_form_item = :formItemId';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('formItemId'));
	}
} // END class Msg_model
