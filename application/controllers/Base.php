<?php defined('BASEPATH') OR exit('No direct script access allowed');

include_once 'Error_Code.php';

class Base extends MY_Controller
{
	/**
	 * 校验活动是否可编辑
	 * publish_state == CONTEST_PUBLISH_STATE_DRAFT
	 * 可修改所有资料,列表不可见,直接访问不可见
	 * publish_state == CONTEST_PUBLISH_STATE_ON
	 * 可修改所有资料,列表可见,直接访问可见
	 * publish_state == CONTEST_PUBLISH_STATE_SELLING
	 * 不可修改项目资料,列表可见,直接访问可见
	 * publish_state == CONTEST_PUBLISH_STATE_OFF
	 * 不可修改项目资料,列表不可见,直接访问可见(订单列表可查看详情)
	 *
	 * @param  integer $cid 活动ID
	 *
	 * @return \stdClass
	 */
	protected function contestEditVerify($cid, $editItem = false)
	{
		$std        = new stdClass();
		$std->error = 0;

		$this->load->model('Contest_model');
		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			$std->error = Error_Code::ERROR_CONTEST_NOT_EXISTS;

			return $std;
		}

		$std->contestInfo = $contestInfo;


		switch ($contestInfo['publish_state']) {
			case CONTEST_PUBLISH_STATE_DRAFT:
			case CONTEST_PUBLISH_STATE_ON:
			case CONTEST_PUBLISH_STATE_OFF:

				return $std;
				break;
			case CONTEST_PUBLISH_STATE_SELLING:
				if ($editItem) {
					$std->error = Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID;
				}

				return $std;
				break;
			default:
				break;
		}

		return $std;
	}

	protected function catchException(Exception $e)
	{
		$errMsg = array(
			'msg'     => 'Exception occurred',
			'e_file'  => $e->getFile(),
			'e_line'  => $e->getLine(),
			'e_msg'   => $e->getMessage(),
			'e_trace' => $e->getTrace()[0],
		);
		log_message_v2('error', $errMsg);
	}
}
