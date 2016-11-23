<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ .'/Base.php';
/**
 * 赛项目报名表单类
 *
 * @package default
 * @author  : zhaodechang@wesai.com
 **/
class Form extends Base
{
	/**
	 * construct
	 *
	 * @return void
	 **/
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Form_model');
		$this->load->model('Contest_model');
	}

	/**
	 * 新增报名表单
	 *
	 */
	public function add_post()
	{
		$itemId = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$name   = $this->post_check('name', PARAM_NOT_NULL_NOT_EMPTY);

		$verify = $this->formEditVerify($itemId);
		if ($verify < 0) {
			return $this->response_error($verify);
		}

		$form_info = $this->getFormByItemId($itemId);
		if (!empty($form_info)) {
			return $this->response_error(Error_Code::ERROR_FORM_ALREADY_EXISTS);
		}

		$lastid = $this->Form_model->addForm($itemId, $name);

		return $this->response_insert($lastid);
	}

	/**
	 * 报名表单编辑校验
	 *
	 * @param  integer $itemid 表单所属活动项目ID
	 *
	 * @return int
	 */
	private function formEditVerify($itemid)
	{
		// 获取活动项目资料
		$contestItemInfo = $this->Contest_model->getContestItemById($itemid);
		if (empty($contestItemInfo)) {
			return Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS;
		}

		// 活动活动项目状态异常
		if ($contestItemInfo['state'] != CONTEST_ITEM_STATE_OK) {
			return Error_Code::ERROR_CONTEST_ITEMS_STATE_INVALID;
		}

		$verifyContestEdit = $this->contestEditVerify($contestItemInfo['fk_contest'], true);
		if ($verifyContestEdit->error < 0) {
			return $verifyContestEdit->error;
		}

		return 0;
	}

	private function getFormByItemId($itemid)
	{
		return $this->Form_model->getFormByItemId($itemid);
	}

	public function get_by_itemid_get()
	{
		$itemid = $this->get_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$form_info = $this->getFormByItemId($itemid);
		if (empty($form_info)) {
			return $this->response_error(Error_Code::ERROR_FORM_NOT_EXISTS);
		}

		return $this->response_object($form_info);
	}

	/**
	 * 根据表单ID获取详情
	 *
	 */
	public function get_get()
	{
		$formid = $this->get_check('formid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$form_info = $this->Form_model->getFormById($formid);

		if (empty($form_info)) {
			return $this->response_error(Error_Code::ERROR_FORM_NOT_EXISTS);
		}

		return $this->response_object($form_info);
	}

	/**
	 * 更新报名表单
	 *
	 */
	public function update_post()
	{
		$formId  = $this->post_check('formid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$name    = $this->post_check('name', PARAM_NULL_NOT_EMPTY);

		// 获取表单资料
		$form_info = $this->Form_model->getFormById($formId);
		if (empty($form_info)) {
			return $this->response_error(Error_Code::ERROR_FORM_NOT_EXISTS);
		}

		$verify = $this->formEditVerify($form_info['fk_contest_items']);
		if ($verify < 0) {
			return $this->response_error($verify);
		}

		$affected_rows = $this->Form_model->updateForm($formId, $name);

		return $this->response_update($affected_rows);
	}

	public function add_form_item_post()
	{
		$fkEnrolForm  = $this->post_check('fk_enrol_form', PARAM_NOT_NULL_NOT_EMPTY);
		$type         = $this->post_check('type', PARAM_NOT_NULL_NOT_EMPTY);
		$title        = $this->post_check('title', PARAM_NOT_NULL_NOT_EMPTY);
		$isRequired   = $this->post_check('is_required', PARAM_NULL_NOT_EMPTY);
		$optionValues = $this->post_check('option_values', PARAM_NULL_EMPTY);

		//获取最大序号
		$seq = $this->Form_model->getFormItemMaxSeq($fkEnrolForm);

		$params          = array(
				'fk_enrol_form' => $fkEnrolForm,
				'type'          => $type,
				'title'         => $title,
				'is_required'   => $isRequired,
				'seq'           => $seq,
				'option_values' => $optionValues,
		);
		$pkEnrolFormItem = $this->Form_model->addFormItem($params);
		if (empty($pkEnrolFormItem)) {
			return $this->response_error(Error_Code::ERROR_ADD_ENROL_FORM_ITEM_FAILED);
		}

		return $this->response_insert($pkEnrolFormItem);
	}

	public function update_form_item_post()
	{
		$pkEnrolFormItem = $this->post_check('pk_enrol_form_item', PARAM_NOT_NULL_NOT_EMPTY);
		$type            = $this->post_check('type', PARAM_NULL_NOT_EMPTY);
		$title           = $this->post_check('title', PARAM_NULL_NOT_EMPTY);
		$isRequired      = $this->post_check('is_required', PARAM_NULL_NOT_EMPTY);
		$optionValues    = $this->post_check('option_values', PARAM_NULL_NOT_EMPTY);
		$seq             = $this->post_check('seq', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$itemInfo = $this->Form_model->getFormItemById($pkEnrolFormItem);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_ENROL_FORM_ITEM_NOT_EXISTS);
		}

		if ($itemInfo['state'] != ENROL_FORM_ITEM_STATE_OK) {
			return $this->response_error(Error_Code::ERROR_ENROL_FORM_ITEM_STATE_INVALID);
		}

		$params = array(
				'type'          => $type,
				'title'         => $title,
				'is_required'   => $isRequired,
				'seq'           => $seq,
				'option_values' => $optionValues,
		);

		$conditions = array('pk_enrol_form_item' => $pkEnrolFormItem);

		$affectedRows = $this->Form_model->updateFormItem($params, $conditions);

		return $this->response_update($affectedRows);
	}

	public function update_form_item_seqs_post()
	{
		$params = $this->post_check('params', PARAM_NOT_NULL_NOT_EMPTY);

		$params = json_decode($params, true);

		$affectedRows = $this->Form_model->updateFormItemSeqs($params);

		return $this->response_update($affectedRows);
	}

	public function delete_form_item_post()
	{
		$pkEnrolFormItem = $this->post_check('pk_enrol_form_item', PARAM_NOT_NULL_NOT_EMPTY);

		$itemInfo = $this->Form_model->getFormItemById($pkEnrolFormItem);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_ENROL_FORM_ITEM_NOT_EXISTS);
		}

		if ($itemInfo['state'] != ENROL_FORM_ITEM_STATE_OK) {
			return $this->response_update(0);
		}

		$affectedRows = $this->Form_model->deleteFormItem($pkEnrolFormItem);

		return $this->response_update($affectedRows);
	}

	public function list_form_item_get()
	{
		$fkEnrolForm = $this->get_check('fk_enrol_form', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = 1;
		$pageSize   = 100;
		$result     = $this->Form_model->listFormItemByFormId($fkEnrolForm, $pageNumber, $pageSize);

		return $this->response_list($result, count($result), $pageNumber, $pageSize);
	}

	public function get_form_item_get()
	{
		$formItemId = $this->get_check('form_item_id', PARAM_NOT_NULL_NOT_EMPTY);

		$result = $this->Form_model->getFormItemById($formItemId);
		if (empty($result)) {
			return $this->response_error(Error_Code::ERROR_ENROL_FORM_ITEM_NOT_EXISTS);
		}

		return $this->response_object($result);
	}
} // END class
