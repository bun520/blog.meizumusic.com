<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Base.php';

/**
 * 活动活动项目报名订单类
 *
 * @package default
 * @author  : zhaodechang@wesai.com
 **/
class Order extends Base
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
		$this->load->model('Order_model');
	}

	/**
	 * 新增订单
	 * info type json
	 * {
	 *      qid : xxx,
	 *      title : xxx,
	 *      type : xxx,
	 *      value : xxx,
	 * }
	 */
	public function add_post()
	{
		$fk_corp          = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$fk_comp_auth_app = $this->post_check('fk_comp_auth_app', PARAM_NOT_NULL_NOT_EMPTY);
		$uid              = $this->post_check('uid', PARAM_NOT_NULL_NOT_EMPTY);
		$cid              = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$itemId           = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$amount           = $this->post_check('amount', PARAM_NOT_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$ip               = $this->post_check('ip', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$info             = $this->post_check('info', PARAM_NOT_NULL_NOT_EMPTY);
		$shippingAddress  = $this->post_check('shipping_addr', PARAM_NULL_NOT_EMPTY);
		$orderSource      = $this->post_check('order_source', PARAM_NULL_NOT_EMPTY);
		$channelAccount   = $this->post_check('channel_account', PARAM_NULL_NOT_EMPTY);

		$inviteCode = $this->post_check('invite_code', PARAM_NULL_EMPTY);
		$inviteCode = strtoupper($inviteCode);

		// 校验用户是否已经报过名
		$verifyOrder = $this->Order_model->listUserOrder($fk_corp, $fk_comp_auth_app, $uid, null, $cid);
		if (!empty($verifyOrder->total)) {
			return $this->response_error(Error_Code::ERROR_CAN_NOT_REORDER_SAME_CONTEST);
		}

		!empty($orderSource) or $orderSource = ORDER_SOURCE_WEIXIN;

		$orderSourceList = array(
			ORDER_SOURCE_WEIXIN,
			ORDER_SOURCE_APP,
			ORDER_SOURCE_MSITE,
		);
		in_array($orderSource, $orderSourceList) or $orderSource = ORDER_SOURCE_WEIXIN;

		$info = json_decode($info, true);
		if (empty($info)) {
			return $this->response_error(Error_Code::ERROR_PARAM);
		}

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		if ($contestInfo['publish_state'] != CONTEST_PUBLISH_STATE_SELLING) {
			return $this->response_error(Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID);
		}

		switch ($contestInfo['gtype']) {
			case CONTEST_GTYPE_MALATHION:
				// 获取马拉松活动资料
				$malathionInfo = $this->Contest_model->getMalathionById($cid);
				if (empty($malathionInfo)) {
					return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
				}
				break;
		}

		// 获取活动项目资料
		$contestItemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($contestItemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		// 检查活动项目状态是否有效
		if ($contestItemInfo['state'] != CONTEST_ITEM_STATE_OK) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_STATE_INVALID);
		}

		// 检查项目已关门,停售
		if (strtotime($contestItemInfo['end_time']) < time()) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_CLOSED);
		}

		//邀请报名
		if ($contestItemInfo['invite_required'] == CONTEST_ITEM_INVITE_REQUIRED_YES) {
			if (empty($inviteCode)) {
				return $this->response_error(Error_Code::ERROR_CONTEST_ITEM_INVITE_CODE_NECESSARY);
			}
			$inviteCodeVerifyResult = $this->verifyItemInviteCode($itemId, $inviteCode);
			if ($inviteCodeVerifyResult < 0) {
				return $this->response_error($inviteCodeVerifyResult);
			}
		}


		// 检查库存状态
		if ($contestItemInfo['max_stock'] > 0 && $contestItemInfo['cur_stock'] < 1) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_QUOTA_FULFIL);
		}

		// 检查报名费用是否契合
		if (intval($amount) !== intval($contestItemInfo['fee'])) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_AMOUNT);
		}

		// 获取活动项目报名表
		$formInfo = $this->Form_model->getFormByItemId($itemId);
		if (empty($formInfo)) {
			return $this->response_error(Error_Code::ERROR_FORM_NOT_EXISTS);
		}

		//获取报名表问题列表
		$formItemList = $this->Form_model->listFormItemByFormId($formInfo['pk_enrol_form'], 1, 100);
		if (empty($formItemList)) {
			return $this->response_error(Error_Code::ERROR_ENROL_FORM_ITEM_NOT_EXISTS);
		}

		$infoFormItemKeys = array();
		foreach ($info as $k => $v) {
			$infoFormItemKeys[] = $v['qid'];
		}

		$formItemKeys = array();
		foreach ($formItemList as $k => $v) {
			if ($v['is_required'] && !in_array($v['pk_enrol_form_item'], $infoFormItemKeys)) {
				return $this->response_error(Error_Code::ERROR_FORM_INPUT_INVALID);
			}

			$formItemKeys[] = $v['pk_enrol_form_item'];
		}

		//清除不合法的输入项
		$enrolInfo = array();
		foreach ($info as $k => $v) {
			if (!in_array($v['qid'], $formItemKeys)) {
				continue;
			}

			$v['fk_enrol_form_item'] = $v['qid'];
			unset($v['qid']);
			$enrolInfo[] = $v;
		}
		unset($info);

		$std = new stdClass();

		$shippingAddress = strval($shippingAddress);

		$maxVerify = $contestItemInfo['max_verify'];

		// 创建订单
		$orderObj = $this->Order_model->addOrder(
			$uid, $cid, $itemId, $amount, $ip, $enrolInfo, $shippingAddress,
			$orderSource, $fk_corp, $fk_comp_auth_app, $channelAccount, $maxVerify
		);

		// 订单创建异常
		if ($orderObj->error < 0) {
			log_message('error', __METHOD__ . '|' . json_encode($orderObj));

			return $this->response_error(Error_Code::ERROR_ORDER_CREATE_FAILED);
		}

		$orderId = $orderObj->orderId;

		if (!empty($inviteCode)) {
			$result = $this->Contest_model->useInviteCode($itemId, $inviteCode, $orderId);
			if (empty($result)) {
				log_message_v2(
					'error', array(
						       'msg'        => 'use invite code failed',
						       'itemId'     => $itemId,
						       'inviteCode' => $inviteCode,
						       'orderId'    => $orderId,
					       )
				);
			}
		}

		$std->orderid = $orderId;

		$this->load->model('Msg_model');
		// 仅微信提交的订单才需要进行文件下载，再上传
		if ($orderSource == ORDER_SOURCE_WEIXIN) {
			@$this->Msg_model->sendMsgOrderFileUploadFromWeixin($orderId);
		}

		//更新订单状态到支付中
		$outTradeNo = $this->updateOrderStateToPaying($orderId);
		if ($outTradeNo < 0) {
			return $this->response_error(Error_Code::ERROR_ORDER_PREPAY_FAILED);
		}

		// 0元报名
		if ($amount == 0) {
			$this->updateOrderStateToCompleted($orderId);
		}

		$std->out_trade_no = $outTradeNo;

		return $this->response_object($std);
	}

	/**
	 * 校验报名邀请码是否合法
	 *
	 * @param $itemId
	 * @param $code
	 *
	 * @return int
	 */
	private function verifyItemInviteCode($itemId, $code)
	{
		$codeInfo = $this->Contest_model->getInviteCodeByCode($itemId, $code);
		if (empty($codeInfo)) {
			return Error_Code::ERROR_CONTEST_ITEM_INVITE_CODE_NOT_EXISTS;
		}

		if ($codeInfo['state'] == CONTEST_ITEM_INVITE_CODE_STATE_USED) {
			return Error_Code::ERROR_CONTEST_ITEM_INVITE_CODE_USED;
		}

		if ($codeInfo['state'] == CONTEST_ITEM_INVITE_CODE_STATE_EXPIRED) {
			return Error_Code::ERROR_CONTEST_ITEM_INVITE_CODE_EXPIRED;
		}

		return 0;
	}

	private function updateOrderStateToPaying($oid)
	{
		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_INIT) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		return $this->Order_model->changeStateToPaying($oid);
	}

	/**
	 * 更新订单状态到“支付失败”
	 */
	public function change_state_failed_post()
	{
		$oid = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 检查订单状态
		if (!in_array($order_info['state'], [ORDER_STATE_INIT, ORDER_STATE_PAYING])) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		$affected_rows = $this->Order_model->changeStateToFailed($oid, $order_info['state']);

		return $this->response_update($affected_rows);
	}

	/**
	 * 更新订单状态到“支付完成”
	 */
	public function change_state_completed_post()
	{
		$oid           = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$paidTime      = $this->post_check('paid_time', PARAM_NOT_NULL_NOT_EMPTY);
		$channelId     = $this->post_check('channel_id', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$transactionId = $this->post_check('transaction_id', PARAM_NOT_NULL_NOT_EMPTY);

		$result = $this->updateOrderStateToCompleted($oid, $paidTime, $channelId, $transactionId);

		if ($result <= 0) {
			return $this->response_error($result);
		}

		return $this->response_update($result);
	}

	/**
	 * 更新订单状态为“支付完成”， 同时更新完成支付的用户账户和支付通道交易ID
	 *
	 * @param  integer $oid 订单ID
	 * @param string   $paidTime
	 *
	 * @param int      $channelId
	 *
	 * @param string   $transactionId
	 *
	 * @return int
	 * @internal param string $channel_account 完成支付的用户账户
	 * @internal param string $channel_transaction_id 支付通道交易ID
	 * @internal param string $channel_id
	 */
	private function updateOrderStateToCompleted($oid, $paidTime = '', $channelId = 0, $transactionId = '')
	{
		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return Error_Code::ERROR_ORDER_NOT_EXISTS;
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_PAYING) {
			return Error_Code::ERROR_ORDER_INVALID_STATE;
		}

		$result =  $this->Order_model->changeStateToCompleted($oid, $paidTime, $channelId, $transactionId);


		$this->load->model('Msg_model');

		$this->Msg_model->sendMsgOrderCompleted($oid);

		return $result;
	}

	/**
	 * 更新订单状态到“订单关闭”
	 */
	public function change_state_closed_post()
	{
		$oid = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_COMPLETED) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		$affected_rows = $this->Order_model->changeStateToClosed($oid);

		return $this->response_update($affected_rows);
	}

	/**
	 * 更新订单状态到“退款中”
	 */
	public function change_state_refunding_post()
	{
		$oid           = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$out_refund_no = $this->updateOrderStateToRefunding($oid);
		if ($out_refund_no < 0) {
			return $this->response_error($out_refund_no);
		}

		// 发送消息申请退款
		$this->load->model('Msg_model');
		$this->Msg_model->sendMsgOrderRefundApply($oid, $out_refund_no);

		return $this->response_object(compact('out_refund_no'));
	}

	private function updateOrderStateToRefunding($oid)
	{
		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return Error_Code::ERROR_ORDER_NOT_EXISTS;
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_COMPLETED) {
			return Error_Code::ERROR_ORDER_INVALID_STATE;
		}

		return $this->Order_model->changeStateToRefunding($oid);
	}

	/**
	 * 更新订单状态到“退款失败”
	 */
	public function change_state_refund_failed_post()
	{
		$oid = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_REFUNDING) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		$affected_rows = $this->Order_model->changeStateToRefundFailed($oid);

		return $this->response_update($affected_rows);
	}

	/**
	 * 更新订单状态到“退款完成”
	 */
	public function change_state_refunded_post()
	{
		$oid = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取订单资料
		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 检查订单状态
		if ($order_info['state'] != ORDER_STATE_REFUNDING) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		$affected_rows = $this->Order_model->changeStateToRefundCompleted($oid);

		return $this->response_update($affected_rows);
	}

	/**
	 * 我的订单列表
	 */
	public function mylist_get()
	{
		$uid            = $this->get_check('uid', PARAM_NOT_NULL_NOT_EMPTY);
		$state          = $this->get_check('state', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$cid            = $this->get_check('cid', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$page           = $this->get_check('page', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$size           = $this->get_check('size', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$rtnContestInfo = $this->get_check('contest_info', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		$fk_corp          = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$fk_comp_auth_app = $this->get_check('fk_comp_auth_app', PARAM_NOT_NULL_NOT_EMPTY);

		$page > 0 or $page = 1;
		$size > 0 or $size = 10;
		$size < 50 or $size = 50;

		$result = $this->Order_model->listUserOrder($fk_corp, $fk_comp_auth_app, $uid, $state, $cid, $page, $size);

		// 如果不需要返回活动详情
		if ($rtnContestInfo != 1) {
			return $this->response_list($result->data, $result->total, $page, $size);
		}

		$cids    = array();
		$itemids = array();
		foreach ($result->data as $v) {
			$cids[]    = $v['fk_contest'];
			$itemids[] = $v['fk_contest_items'];
		}

		$contestInfos = array();
		if (!empty($cids)) {
			$contestInfos = $this->Contest_model->getContestByIds($cids);
		}

		$itemInfos = array();
		if (!empty($itemids)) {
			$itemInfos = $this->Contest_model->getContestItemByItemIds($itemids);
		}

		foreach ($result->data as $key => $value) {
			$result->data[$key]['contest_info']      = null;
			$result->data[$key]['contest_item_info'] = null;
			if (!empty($contestInfos)) {
				foreach ($contestInfos as $cv) {
					if ($value['fk_contest'] == $cv['pk_contest']) {
						$result->data[$key]['contest_info'] = $cv;
						break;
					}
				}
			}

			if (!empty($itemInfos)) {
				foreach ($itemInfos as $iv) {
					if ($value['fk_contest_items'] == $iv['pk_contest_items']) {
						$result->data[$key]['contest_item_info'] = $iv;
						break;
					}
				}
			}
		}

		return $this->response_list($result->data, $result->total, $page, $size);
	}

	/**
	 * [search_get description]
	 */
	public function search_get()
	{
		$fk_corp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$cname   = $this->get_check('cname', PARAM_NULL_EMPTY);
		$citems  = $this->get_check('citems', PARAM_NULL_EMPTY);
		$state   = $this->get_check('state', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$idno    = $this->get_check('idno', PARAM_NULL_EMPTY);
		$mobile  = $this->get_check('mobile', PARAM_NULL_EMPTY);
		$orderid = $this->get_check('orderid', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		// $lottery_state          = $this->get_check('lottery_state', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		// $deliver_gear           = $this->get_check('deliver_gear', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		// $channel_transaction_id = $this->get_check('trade_no', PARAM_NULL_EMPTY);
		$page  = $this->get_check('page', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$size  = $this->get_check('size', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$start = $this->get_check('start', PARAM_NULL_EMPTY);
		$end   = $this->get_check('end', PARAM_NULL_EMPTY);

		$page > 0 or $page = 1;
		$size > 0 or $size = 10;
		$size < 50 or $size = 50;

		!empty($start) or $start = '1970-01-01';
		!empty($end) or $end = date('Y-m-d 23:59:59');

		$start = strtotime(date('Y-m-d 00:00:00', strtotime($start)));
		$end   = strtotime(date('Y-m-d 23:59:59', strtotime($end)));

		$rs    = array();
		$total = 0;
		if (!empty($orderid)) {
			$orderInfo = $this->Order_model->getOrderById($orderid);
			if (!empty($orderInfo)) {
				$contestInfo = $this->Contest_model->getContestById($orderInfo['fk_contest']);

				$contestItemInfo = $this->Contest_model->getContestItemById($orderInfo['fk_contest_items']);

				$orderInfo['contest_info']       = $contestInfo;
				$orderInfo['contest_items_info'] = $contestItemInfo;

				$rs[] = $orderInfo;
				$total++;
			}

			return $this->response_list($rs, $total, $page, $size);
		}

		$params = compact(
			'fk_corp',
			'cname',
			'citems',
			'state',
			'idno',
			'mobile',
			'orderid',
			// 'lottery_state',
			// 'deliver_gear',
			'channel_transaction_id',
			'page',
			'size',
			'start',
			'end'
		);


		$result = $this->Order_model->searchOrderManage($params);
		$total  = $result->total;
		if (empty($result->result)) {
			return $this->response_list(array(), 0, $page, $size);
		}

		$orderIds = array();
		foreach ($result->result as $key => $value) {
			$orderIds[] = $value;
		}

		$orderList = $this->Order_model->getByIds($orderIds);
		if (empty($orderList)) {
			return $this->response_list(array(), 0, $page, $size);
		}

		$contestIds       = array();
		$itemIds          = array();
		$orderListWithKey = array();
		foreach ($orderList as $value) {
			$orderListWithKey[$value['pk_order']] = $value;
			$contestIds[]                         = $value['fk_contest'];
			$itemIds[]                            = $value['fk_contest_items'];
		}
		unset($orderList);

		$contestList = $this->Contest_model->getContestByIds($contestIds);
		$itemList    = $this->Contest_model->getContestItemByItemIds($itemIds);
		if (empty($contestList) || empty($itemList)) {
			return $this->response_list(array(), 0, $page, $size);
		}

		$contestListWithKey = array();
		foreach ($contestList as $contest) {
			$contestListWithKey[$contest['pk_contest']] = $contest;
		}
		unset($contestList);

		$itemListWithKey = array();
		foreach ($itemList as $item) {
			$itemListWithKey[$item['pk_contest_items']] = $item;
		}
		unset($itemList);

		$orderListFinal = array();
		foreach ($orderIds as $orderId) {
			if (!array_key_exists($orderId, $orderListWithKey)) {
				continue;
			}
			$orderInfo = $orderListWithKey[$orderId];
			if (!array_key_exists($orderInfo['fk_contest'], $contestListWithKey)) {
				continue;
			}
			if (!array_key_exists($orderInfo['fk_contest_items'], $itemListWithKey)) {
				continue;
			}

			$orderInfo['contest_info']       = $contestListWithKey[$orderInfo['fk_contest']];
			$orderInfo['contest_items_info'] = $itemListWithKey[$orderInfo['fk_contest_items']];

			$orderListFinal[] = $orderInfo;
		}
		unset($contestListWithKey, $itemListWithKey, $orderListWithKey);

		return $this->response_list($orderListFinal, $total, $page, $size);
	}

	/**
	 * 获取订单详情
	 */
	public function get_get()
	{
		$oid            = $this->get_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$rtnContestInfo = $this->get_check('contest_info', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		// 获取订单资料
		$orderInfo = $this->Order_model->getOrderById($oid);
		if (empty($orderInfo)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		// 获取报名详情
		$enrolInfo = $this->Order_model->listEnrolInfo($oid);

		$orderInfo['enrol_info'] = $enrolInfo;

		// 不获取活动资料，直接返回订单数据
		if ($rtnContestInfo != 1) {
			return $this->response_object($orderInfo);
		}

		// $enrolInfoTypes = array('namebox', 'sexbox', 'idbox', 'phonebox', 'emailbox');
		//
		// $enrolData = array();
		// foreach ($enrolInfo as $k => $v) {
		// 	if (!in_array($v['type'], $enrolInfoTypes)) {
		// 		continue;
		// 	}
		// 	$enrolData[] = $v;
		// }
		// unset($enrolInfo);
		//
		// $orderInfo['enrol_info'] = $enrolData;

		$contestInfo = $this->Contest_model->getContestById($orderInfo['fk_contest']);
		switch ($contestInfo['gtype']) {
			case CONTEST_GTYPE_MALATHION:
				$malathionInfo = $this->Contest_model->getMalathionById($orderInfo['fk_contest']);
				if (empty($malathionInfo)) {
					return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
				}
				$contestInfo = array_merge($contestInfo, $malathionInfo);
				break;
		}

		$orderInfo['contest_info']      = $contestInfo;
		$orderInfo['contest_item_info'] = $this->Contest_model->getContestItemById($orderInfo['fk_contest_items']);

		return $this->response_object($orderInfo);
	}

	/**
	 * 申请退款
	 */
	public function refund_post()
	{
		$oid = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$uid = $this->post_check('uid', PARAM_NOT_NULL_NOT_EMPTY);

		$order_info = $this->Order_model->getOrderById($oid);
		if (empty($order_info)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		if ($order_info['fk_user'] != $uid) {
			return $this->response_error(Error_Code::ERROR_ORDER_REFUND_UID_NOT_MATCH);
		}

		$out_refund_no = $this->updateOrderStateToRefunding($oid);
		if ($out_refund_no < 0) {
			return $this->response_error($out_refund_no);
		}

		return $this->response_object(compact('out_refund_no'));
	}

	public function contest_order_export_post()
	{
		$fk_corp = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$cname   = $this->post_check('cname', PARAM_NOT_NULL_NOT_EMPTY);
		$state   = $this->post_check('state', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$page    = 1;
		$size    = 1;

		$params = compact('fk_corp', 'cname', 'page', 'size');
		$result = $this->Order_model->searchOrderManage($params);

		if (empty($result->result)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}
		$oid       = $result->result[0];
		$orderInfo = $this->Order_model->getOrderById($oid);
		if (empty($orderInfo)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}
		$cid      = $orderInfo['fk_contest'];
		$std      = new stdClass();
		$std->cid = $cid;
		// 发送导出订单消息
		$this->load->model('Msg_model');
		$this->Msg_model->send_msg_contest_order_export($cid, $state);

		return $this->response_object($std);
	}

	public function get_specified_enrol_info_get()
	{
		$oid = $this->get_check('oid', PARAM_NOT_NULL_NOT_EMPTY);
		$key = $this->get_check('key', PARAM_NOT_NULL_NOT_EMPTY);

		$result = $this->Order_model->getSpecifiedEnrolInfo($oid, $key);

		return $this->response_object($result);
	}

	public function list_order_expired_get()
	{
		$pageNumber = $this->get_check('page', PARAM_NOT_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NOT_NULL_NOT_EMPTY);

		$pageNumber or $pageNumber = 1;
		$pageNumber > 0 or $pageNumber = 1;
		$pageSize or $pageSize = 100;
		$pageSize < 100 or $pageSize = 100;

		$result = $this->Order_model->listOrderExpired($pageNumber, $pageSize);

		return $this->response_list($result, count($result), $pageNumber, $pageSize);
	}

	public function update_order_state_track_post()
	{
		$oid   = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY);
		$state = $this->post_check('state', PARAM_NOT_NULL_NOT_EMPTY);

		$result = $this->Order_model->updateOrderStateTrack($oid, $state);

		return $this->response_update($result);
	}

	public function list_enrol_info_get()
	{
		$oid = $this->get_check('oid', PARAM_NOT_NULL_NOT_EMPTY);

		$enrolInfo = $this->Order_model->listEnrolInfo($oid);

		return $this->response_object($enrolInfo);
	}

	public function update_enrol_info_post()
	{
		$pkEnrolInfo = $this->post_check('pk_enrol_info', PARAM_NOT_NULL_NOT_EMPTY);
		$value       = $this->post_check('value', PARAM_NOT_NULL_NOT_EMPTY);

		$result = $this->Order_model->updateEnrolValue($pkEnrolInfo, $value);

		return $this->response_update($result);
	}

	public function list_lottery_success_order_get()
	{
		$cid           = $this->get_check('cid', PARAM_NOT_NULL_NOT_EMPTY);
		$state         = $this->get_check('state', PARAM_NULL_NOT_EMPTY);
		$lottery_state = $this->get_check('lottery_state', PARAM_NULL_NOT_EMPTY);
		$pageNumber    = $this->get_check('page', PARAM_NOT_NULL_NOT_EMPTY);
		$pageSize      = $this->get_check('size', PARAM_NOT_NULL_NOT_EMPTY);

		$pageNumber or $pageNumber = 1;
		$pageNumber > 0 or $pageNumber = 1;
		$pageSize or $pageSize = 100;
		$pageSize < 100 or $pageSize = 100;

		$result = $this->Order_model->listRegSuccessOrder($cid, $pageNumber, $pageSize, $state, $lottery_state);

		return $this->response_list($result, count($result), $pageNumber, $pageSize);
	}

	public function list_get()
	{
		$pageNumber = $this->get_check('page', PARAM_NOT_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NOT_NULL_NOT_EMPTY);

		$pageNumber or $pageNumber = 1;
		$pageNumber > 0 or $pageNumber = 1;
		$pageSize or $pageSize = 100;
		$pageSize < 100 or $pageSize = 100;

		$result = $this->Order_model->listOrder($pageNumber, $pageSize);

		return $this->response_list($result, count($result), $pageNumber, $pageSize);
	}

	public function get_payinfo_by_out_trade_no_get()
	{
		$outTradeNo = $this->get_check('out_trade_no', PARAM_NOT_NULL_NOT_EMPTY);
		$oid        = intval(substr($outTradeNo, -10));

		$orderInfo = $this->Order_model->getOrderById($oid);
		if (empty($orderInfo)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		if ($orderInfo['state'] != ORDER_STATE_PAYING) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		$contestInfo = $this->Contest_model->getContestById($orderInfo['fk_contest']);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		$itemInfo = $this->Contest_model->getContestItemById($orderInfo['fk_contest_items']);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		//给支付通道的超时时间减少1分钟,保证通道支付完成后,业务方这边的订单不会提前失效
		$expiresIn = (ORDER_PAY_TIME_LIMIT - 1) * 60 - (time() - strtotime($orderInfo['ctime']));
		if ($expiresIn < 0) {
			return $this->response_error(Error_Code::ERROR_ORDER_ALREADY_EXPIRED);
		}

		$payInfo                     = array();
		$payInfo['contest_name']     = $contestInfo['name'];
		$payInfo['contest_location'] = $contestInfo['location'];
		$payInfo['contest_sdate']    = $contestInfo['sdate'];
		$payInfo['item_name']        = $itemInfo['name'];
		$payInfo['amount']           = $orderInfo['amount'];

		$result                     = array();
		$result['authorizer_apppk'] = $orderInfo['fk_component_authorizer_app'];
		$result['openid']           = $orderInfo['channel_account'];
		$result['out_trade_no']     = $outTradeNo;
		$result['amount']           = $orderInfo['amount'];
		$result['ctime']            = $orderInfo['ctime'];
		$result['expire_seconds']   = $expiresIn;
		$result['remark']           = $payInfo['item_name'];
		$result['payinfo']          = $payInfo;

		return $this->response_object($result);

	}

	public function verify_post()
	{
		$orderId = $this->post_check('oid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$itemId  = $this->post_check('item_id', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$pkCorp  = $this->post_check('pk_corp', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$userId  = $this->post_check('user_id', PARAM_NOT_NULL_NOT_EMPTY);

		$orderInfo = $this->Order_model->getOrderById($orderId);
		if (empty($orderInfo)) {
			return $this->response_error(Error_Code::ERROR_ORDER_NOT_EXISTS);
		}

		$itemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($orderInfo['fk_contest_items'] != $itemId) {
			return $this->response_error(Error_Code::ERROR_ORDER_VERIFY_ITEM_NOT_MATCH);
		}

		// if ($itemInfo['start_time'] < date('Y-m-d H:i:s')) {
		// 	return $this->response_error(Error_Code::ERROR_ORDER_VERIFY_ITEM_HAS_ALREADY_STARTED);
		// }

		if ($orderInfo['state'] != ORDER_STATE_CLOSED) {
			return $this->response_error(Error_Code::ERROR_ORDER_INVALID_STATE);
		}

		if ($orderInfo['max_verify'] > 0 && $orderInfo['verify_number'] >= $orderInfo['max_verify']) {
			return $this->response_error(Error_Code::ERROR_ORDER_VERIFY_OVERFLOW);
		}

		$affectedRows = $this->Order_model->VerifyOrder($orderId, $pkCorp, $userId, $orderInfo['verify_number']);

		return $this->response_update($affectedRows);
	}
} // END class
