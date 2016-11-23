<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 消息队列处理类
 *
 * @author: zhaodechang@wesai.com
 **/
class Msg_model extends MY_Model
{
	/**
	 * init
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->config('config');
		$this->load->library('Redis_List_Client', $this->config->config['redis']);
	}

	/**
	 * 发送活动状态变更消息
	 *
	 **/
	public function sendMsgMalathionStateChange($cid, $state)
	{
		$msg          = array();
		$msg['cid']   = $cid;
		$msg['state'] = $state;
		$msg['ctime'] = date('Y-m-d H:i:s');
		switch ($state) {
			case MALATHION_STATE_RECEIVING:
				$action = 'receiving';
				break;
			case MALATHION_STATE_ROLL_CALL_START:
				$action = 'rollcall_start';
				break;
			case MALATHION_STATE_CONTEST_OVER:
				$action = 'contest_over';
				break;
		}
		$msg['action'] = $action;

		return $this->_send_msg(MQ_TOPIC_MALATHION_STATE_CHANGE, $msg);
	}

	/**
	 * 发送消息到redis
	 *
	 * @param  string $topic
	 * @param  mixed  $msg
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function _send_msg($topic, $msg)
	{
		try {
			return $this->redis_list_client->LeftPush($topic, json_encode($msg));
		} catch (Exception $e) {
			log_message('error', 'redis|list|' . $e->getMessage());
			throw new Exception($e->getMessage(), Error_Code::ERROR_REDIS);

		}
	}

	public function sendMsgOrderCompleted($orderid)
	{
		$msg          = array();
		$msg['oid']   = $orderid;
		$msg['ctime'] = date('Y-m-d H:i:s');

		return $this->_send_msg(MQ_TOPIC_ORDER_PAY_COMPLETED, $msg);
	}

	public function sendMsgOrderFileUploadFromWeixin($oid)
	{
		$msg           = array();
		$msg['oid']    = $oid;
		$msg['source'] = 'weixin';
		$msg['ctime']  = date("Y-m-d H:i:s");

		return $this->_send_msg(MQ_TOPIC_WX_FILE_UPLOAD, $msg);
	}

	/**
	 * 发送订单导出消息
	 *
	 * @param $cid      订单ID
	 * @param $state    订单状态
	 *
	 * @return
	 */
	public function send_msg_contest_order_export($cid, $state)
	{
		$msg = compact('cid', 'state');

		return $this->_send_msg(MQ_TOPIC_CONTEST_ORDER_EXPORT, $msg);
	}

	public function sendMsgCreateContestItemInviteCode($itemId)
	{
		$msg = compact('itemId');

		return $this->_send_msg(MQ_TOPIC_CREATE_CONTEST_ITEM_INVITE_CODE, $msg);
	}

} // END class Msg_model
