<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ .'/Base.php';

/**
 * 马拉松类
 *
 * @package default
 * @author  : zhaodechang@wesai.com
 **/
class Malathion extends Base
{
	/**
	 * construct
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Contest_model');
		$this->load->model('Form_model');
	}

	/**
	 * 资格审查中
	 *
	 **/
	public function change_state_reviewing_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		if ($contestInfo['publish_state'] == CONTEST_PUBLISH_STATE_DRAFT) {
			return $this->response_error(Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_DRAFT) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		if ($malathionInfo['lottery'] == MALATHION_LOTTERY_NO) {
			return $this->response_error(Error_Code::ERROR_MALATHION_LOTTERY_INVALID);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToReviewing($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_4_TO_5_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 抽签中
	 *
	 **/
	public function change_state_balloting_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_REVIEWING) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToBalloting($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_5_TO_6_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 抽签结束
	 *
	 **/
	public function change_state_ballot_completed_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_BALLOTING) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToBallotCompleted($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_6_TO_7_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 装备领取中
	 *
	 **/
	public function change_state_receiving_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_BALLOT_COMPLETE) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateFromBallotCompletedToReceiving($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_7_TO_8_FAIL);
		}

		// 发送消息通知
		$this->load->model('Msg_model');
		$this->Msg_model->sendMsgMalathionStateChange($cid, MALATHION_STATE_RECEIVING);

		return $this->response_update($affectRows);
	}

	/**
	 * 装备领取完成
	 *
	 **/
	public function change_state_receive_completed_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_RECEIVING) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToReceiveCompleted($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_8_TO_9_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 检录中
	 *
	 **/
	public function change_state_rollcall_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_RECEIVE_COMPLETE) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToRollcall($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_9_TO_10_FAIL);
		}

		// 发送消息通知
		$this->load->model('Msg_model');
		$this->Msg_model->sendMsgMalathionStateChange($cid, MALATHION_STATE_ROLL_CALL_START);

		return $this->response_update($affectRows);
	}

	/**
	 * 检录完成
	 *
	 **/
	public function change_state_rollcall_completed_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_ROLL_CALL_START) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToRollcallCompleted($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_10_TO_11_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 竞赛开始
	 *
	 **/
	public function change_state_start_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_ROLL_CALL_END) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToContestStart($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_11_TO_12_FAIL);
		}

		return $this->response_update($affectRows);
	}

	/**
	 * 竞赛结束
	 *
	 **/
	public function change_state_over_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_CONTEST_START) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		$affectRows = $this->Contest_model->changeMalathionStateToContestOver($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_12_TO_13_FAIL);
		}

		// 发送消息通知
		$this->load->model('Msg_model');
		$this->Msg_model->sendMsgMalathionStateChange($cid, MALATHION_STATE_CONTEST_OVER);

		return $this->response_update($affectRows);
	}

	/**
	 * 暂存到装备领取中
	 *
	 **/
	public function change_state_draft_to_receiving_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		// 获取马拉松资料
		$malathionInfo = $this->Contest_model->getMalathionById($cid);
		if (empty($malathionInfo)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
		}

		if ($malathionInfo['state'] != MALATHION_STATE_DRAFT) {
			return $this->response_error(Error_Code::ERROR_MALATHION_INVALID_STATE);
		}

		if ($malathionInfo['lottery'] == MALATHION_LOTTERY_YES) {
			return $this->response_error(Error_Code::ERROR_MALATHION_LOTTERY_INVALID);
		}

		$affectRows = $this->Contest_model->changeMalathionStateFromDraftToReceiving($cid);
		if (empty($affectRows)) {
			return $this->response_error(Error_Code::ERROR_MALATHION_STATE_4_TO_8_FAIL);
		}

		$this->load->model('Msg_model');
		$this->Msg_model->sendMsgMalathionStateChange($cid, MALATHION_STATE_RECEIVING);

		return $this->response_update($affectRows);
	}
} // END class
