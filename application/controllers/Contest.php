<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Base.php';

/**
 * 活动类
 *
 * @package default
 * @author  : zhaodechang@wesai.com
 **/
class Contest extends Base
{
	/**
	 * 构造函数
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Contest_model');
		$this->load->model('Form_model');
		$this->load->model('Msg_model');
	}

	/**
	 * 新增活动
	 *
	 */
	public function add_post()
	{
		$name          = $this->post_check('name', PARAM_NOT_NULL_NOT_EMPTY);
		$ename         = $this->post_check('ename', PARAM_NULL_EMPTY);
		$intro         = $this->input->post('intro');
		$logo          = $this->post_check('logo', PARAM_NOT_NULL_NOT_EMPTY);
		$poster        = $this->post_check('poster', PARAM_NOT_NULL_NOT_EMPTY);
		$banner        = $this->post_check('banner', PARAM_NOT_NULL_NOT_EMPTY);
		$sdate         = $this->post_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$location      = $this->post_check('location', PARAM_NOT_NULL_NOT_EMPTY);
		$source        = $this->post_check('source', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$gtype         = $this->post_check('gtype', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$fk_corp       = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$fk_corp_user  = $this->post_check('fk_corp_user', PARAM_NOT_NULL_NOT_EMPTY);
		$level         = $this->post_check('level', PARAM_NULL_EMPTY);
		$lottery       = $this->post_check('lottery', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$deliver_gear  = $this->post_check('deliver_gear', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$publish_state = CONTEST_PUBLISH_STATE_DRAFT;

		$country_scope = $this->post_check('country_scope', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$service_tel   = $this->post_check('service_tel', PARAM_NOT_NULL_NOT_EMPTY);

		$contest_source_list = array(
			CONTEST_SOURCE_WISDOM,
			CONTEST_SOURCE_WESAI,
		);

		in_array($source, $contest_source_list) or $source = CONTEST_SOURCE_WESAI;

		$contest_params = compact(
			'name', 'ename', 'intro', 'logo', 'poster', 'banner', 'sdate', 'gtype', 'location', 'source',
			'publish_state', 'fk_corp', 'fk_corp_user', 'level', 'lottery', 'deliver_gear', 'country_scope',
			'service_tel'
		);

		$params = compact('contest_params');

		switch ($gtype) {
			case CONTEST_GTYPE_MALATHION:
				$malathion_params = array('state' => MALATHION_STATE_DRAFT);

				$params['malathion_params'] = $malathion_params;
				break;
		}

		$cid = $this->Contest_model->addContest($params);

		return $this->response_insert($cid);
	}

	/**
	 * 根据活动ID获取活动资料
	 *
	 */
	public function get_get()
	{
		// 活动ID
		$cid = $this->get_check('cid', PARAM_NOT_NULL_NOT_EMPTY);
		// 是否返回图文简介
		$returnIntro = $this->get_check('intro', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		$contestInfo = $this->Contest_model->getContestById($cid, $returnIntro);

		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		switch ($contestInfo['gtype']) {
			case CONTEST_GTYPE_MALATHION:
				$malathionInfo = $this->Contest_model->getMalathionById($cid);
				if (empty($malathionInfo)) {
					return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
				}
				$contestInfo = array_merge($contestInfo, $malathionInfo);
				break;

			default:
				break;
		}

		return $this->response_object($contestInfo);
	}

	/**
	 * 更新活动信息
	 *
	 * @return  Integer affect rows
	 **/
	public function update_post()
	{
		$cid          = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$name         = $this->post_check('name', PARAM_NULL_NOT_EMPTY);
		$ename        = $this->post_check('ename', PARAM_NULL_EMPTY);
		$intro        = $this->input->post('intro');
		$logo         = $this->post_check('logo', PARAM_NULL_NOT_EMPTY);
		$poster       = $this->post_check('poster', PARAM_NULL_NOT_EMPTY);
		$banner       = $this->post_check('banner', PARAM_NULL_NOT_EMPTY);
		$sdate        = $this->post_check('date', PARAM_NULL_NOT_EMPTY);
		$location     = $this->post_check('location', PARAM_NULL_NOT_EMPTY);
		$source       = $this->post_check('source', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$gtype        = $this->post_check('gtype', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$level        = $this->post_check('level', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$lottery      = $this->post_check('lottery', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$deliver_gear = $this->post_check('deliver_gear', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$country_scope = $this->post_check('country_scope', PARAM_NULL_NOT_EMPTY);
		$service_tel   = $this->post_check('service_tel', PARAM_NULL_NOT_EMPTY);

		$contest_source_list = array(
			CONTEST_SOURCE_WISDOM,
			CONTEST_SOURCE_WESAI,
		);

		in_array($source, $contest_source_list) or $source = null;

		$contest_params = compact(
			'name', 'ename', 'intro', 'logo', 'poster', 'banner', 'sdate', 'gtype',
			'location', 'source', 'level', 'lottery', 'deliver_gear', 'country_scope',
			'service_tel'
		);

		$params = compact('contest_params');

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo) or (!empty($contestInfo) && $contestInfo['gtype'] != $gtype)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		switch ($gtype) {
			case CONTEST_GTYPE_MALATHION:
				// 获取马拉松资料
				$malathionInfo = $this->Contest_model->getMalathionById($cid);
				if (empty($malathionInfo)) {
					return $this->response_error(Error_Code::ERROR_MALATHION_NOT_EXISTS);
				}

				$malathion_params           = array('state' => $malathionInfo['state']);
				$params['malathion_params'] = $malathion_params;
				break;
		}

		$affected_rows = $this->Contest_model->updateContest($cid, $params);

		return $this->response_update($affected_rows);
	}

	/**
	 * 添加地理位置
	 *
	 */
	public function tag_location_add_post()
	{
		$cid  = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$tags = $this->post_check('tags', PARAM_NOT_NULL_NOT_EMPTY);

		$tag_list = json_decode($tags, true);
		if (empty($tag_list) || !is_array($tag_list)) {
			return $this->response_error(Error_Code::ERROR_PARAM);
		}

		$affected_rows = 0;
		$affected_rows += $this->Contest_model->deleteLocationTag($cid);

		foreach ($tag_list as $key => $value) {
			if (empty($value) || !is_array($value)
			    || !array_key_exists('name', $value)
			    || !array_key_exists('level', $value)
			    || empty($value['name'])
			    || empty($value['level'])
			) {
				continue;
			}
			$last_insert_id = $this->Contest_model->addLocationTag($cid, $value['name'], $value['level']);
			if (!empty($last_insert_id)) {
				$affected_rows++;
			}
		}

		return $this->response_update($affected_rows);
	}

	/**
	 * 添加组织单位
	 *
	 */
	public function tag_units_add_post()
	{
		$cid  = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$tag  = $this->post_check('tag', PARAM_NOT_NULL_NOT_EMPTY);
		$role = $this->post_check('role', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$last_insert_id = $this->Contest_model->addUnitsTag($cid, $tag, $role);
		if (false === $last_insert_id) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ADD_TAG_UNITS_FAIL);
		}

		return $this->response_insert($last_insert_id);
	}


	/**
	 * 获取活动地理位置列表
	 *
	 * @return
	 */
	public function list_contest_tag_location_get()
	{
		$cid      = $this->get_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$tag_list = $this->Contest_model->listContestTagLocation($cid);

		$this->load->helper('usort');
		usort($tag_list, compare_array_int('level'));

		return $this->response_list($tag_list, count($tag_list));
	}

	/**
	 * 获取活动组织单位列表
	 *
	 */
	public function list_contest_tag_units_get()
	{
		$cid      = $this->get_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$tag_list = $this->Contest_model->listContestTagUnits($cid);

		$this->load->helper('usort');
		usort($tag_list, compare_array_int('role'));

		return $this->response_list($tag_list, count($tag_list), 20);
	}

	/**
	 *
	 */
	public function list_tag_units_get()
	{
		$page = $this->get_check('page', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$size = $this->get_check('size', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$page > 0 or $page = 1;

		$size > 0 or $size = 20;
		$size < 100 or $size = 100;

		$result = $this->Contest_model->listTagUnits($page, $size);

		return $this->response_list($result, count($result), $page, $size);
	}

	/**
	 * 根据关键字查询匹配的组织单位
	 *
	 */
	public function tag_units_search_get()
	{
		$tag      = $this->get_check('tag', PARAM_NOT_NULL_NOT_EMPTY);
		$page     = 1;
		$size     = 20;
		$tag_list = $this->Contest_model->searchTagUnits($tag, $page, $size);
		$result   = array();
		if (empty($tag_list->result)) {
			return $this->response_list($result, $tag_list->total, $page, $size);
		}
		$result = $this->Contest_model->getTagUnitByIds($tag_list->result);

		return $this->response_list($result, $tag_list->total, $page, $size);
	}

	/**
	 * 筛选活动front
	 *
	 */
	public function search_get()
	{
		$location   = $this->get_check('location', PARAM_NULL_EMPTY);
		$gtype      = $this->get_check('gtype', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$minDate    = $this->get_check('min_date', PARAM_NULL_EMPTY);
		$maxDate    = $this->get_check('max_date', PARAM_NULL_EMPTY);
		$name       = $this->get_check('name', PARAM_NULL_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$pageSize   = $this->get_check('size', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$fkCorp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 10;
		$pageSize < 50 or $pageSize = 50;

		!empty($minDate) or $minDate = '1970-01-01';
		!empty($maxDate) or $maxDate = date('Y-m-d 23:59:59');

		$minTime = strtotime(date('Y-m-d 00:00:00', strtotime($minDate)));
		$maxTime = strtotime(date('Y-m-d 23:59:59', strtotime($maxDate)));

		if (!empty($location)) {
			$location = explode(',', $location);
		}

		$result     = array();
		$contestIds = $this->Contest_model->searchContestFront($fkCorp, $name, $location, $gtype, $minTime, $maxTime, $pageNumber, $pageSize);
		$total      = $contestIds->total;
		if (empty($contestIds->result)) {
			return $this->response_list($result, $total, $pageNumber, $pageSize);
		}

		$contestList = $this->Contest_model->getContestByIds($contestIds->result);

		$this->load->helper('usort');
		usort($contestList, compare_array_str('ctime', 'DESC'));

		return $this->response_list($contestList, $total, $pageNumber, $pageSize);
	}

	/**
	 * 筛选活动manage
	 *
	 */
	public function search_manage_get()
	{
		$name    = $this->get_check('name', PARAM_NULL_EMPTY);
		$gtype   = $this->get_check('gtype', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$state   = $this->get_check('state', PARAM_NULL_EMPTY);
		$page    = $this->get_check('page', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$size    = $this->get_check('size', PARAM_NULL_EMPTY, PARAM_TYPE_INT);
		$fk_corp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$minDate = $this->get_check('min_date', PARAM_NULL_EMPTY);
		$maxDate = $this->get_check('max_date', PARAM_NULL_EMPTY);

		$page > 0 or $page = 1;
		$size > 0 or $size = 10;
		$size < 50 or $size = 50;

		!empty($minDate) or $minDate = '1970-01-01';
		!empty($maxDate) or $maxDate = date('Y-m-d 23:59:59');

		$minTime = strtotime(date('Y-m-d 00:00:00', strtotime($minDate)));
		$maxTime = strtotime(date('Y-m-d 23:59:59', strtotime($maxDate)));

		if (!empty($state)) {
			$state = explode(',', $state);
		}

		$result = array();
		$cids   = $this->Contest_model->searchContestManage($fk_corp, $name, $state, $gtype, $page, $size, $minTime, $maxTime);

		$total = $cids->total;
		if (empty($cids->result)) {
			return $this->response_list($result, $total, $page, $size);
		}

		$contestList = $this->Contest_model->getContestByIds($cids->result);

		$this->load->helper('usort');
		usort($contestList, compare_array_str('ctime', 'DESC'));

		return $this->response_list($contestList, $total, $page, $size);
	}

	/**
	 *
	 */
	public function list_get()
	{
		$fk_corp    = $this->get_check('fk_corp', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$visible    = $this->get_check('visible', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$pageNumber = $this->get_check('page', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$pageSize   = $this->get_check('size', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		$result = $this->Contest_model->listContest($pageNumber, $pageSize, $visible, $fk_corp);

		return $this->response_list($result, count($result), $pageNumber, $pageSize);
	}

	/**
	 * 新增活动项目
	 *
	 */
	public function items_add_post()
	{
		$cid       = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$name      = $this->post_check('name', PARAM_NOT_NULL_NOT_EMPTY);
		$maxStock  = $this->post_check('max_stock', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$fee       = $this->post_check('fee', PARAM_NOT_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$startTime = $this->post_check('start', PARAM_NOT_NULL_NOT_EMPTY);
		$endTime   = $this->post_check('end', PARAM_NOT_NULL_NOT_EMPTY);

		$inviteRequired = $this->post_check('invite_required', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$maxVerify = $this->post_check('max_verify', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		in_array($inviteRequired, [CONTEST_ITEM_INVITE_REQUIRED_YES, CONTEST_ITEM_INVITE_REQUIRED_NO]) or
		$inviteRequired = CONTEST_ITEM_INVITE_REQUIRED_NO;

		isset($maxStock) and ($maxStock >= 0 or $maxStock = 0);
		$maxStock < CONTEST_ITEM_MAX_STOCK or $maxStock = CONTEST_ITEM_MAX_STOCK;

		if ($inviteRequired == CONTEST_ITEM_INVITE_REQUIRED_YES && empty($maxStock)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEM_MUST_SET_MAX_PLAYER);
		}

		$verify = $this->contestEditVerify($cid, true);
		if ($verify->error < 0) {
			return $this->response_error($verify->error);
		}

		$params = array(
			'fk_corp'         => $verify->contestInfo['fk_corp'],
			'fk_contest'      => $cid,
			'name'            => $name,
			'max_stock'       => $maxStock,
			'cur_stock'       => $maxStock,
			'fee'             => $fee,
			'start_time'      => $startTime,
			'end_time'        => $endTime,
			'invite_required' => $inviteRequired,

			'max_verify' => $maxVerify,
		);

		$itemId = $this->Contest_model->addContestItem($params);

		//生成报名邀请码
		if ($inviteRequired == CONTEST_ITEM_INVITE_REQUIRED_YES) {
			$this->Msg_model->sendMsgCreateContestItemInviteCode($itemId);
		}

		return $this->response_insert($itemId);
	}

	/**
	 * 更新活动项目
	 *
	 */
	public function items_update_post()
	{
		$itemId    = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$name      = $this->post_check('name', PARAM_NULL_NOT_EMPTY);
		$maxStock  = $this->input->post('max_stock', true);
		$fee       = $this->post_check('fee', PARAM_NOT_NULL_EMPTY, PARAM_TYPE_NUMBER);
		$startTime = $this->post_check('start', PARAM_NULL_EMPTY);
		$endTime   = $this->post_check('end', PARAM_NULL_EMPTY);

		$inviteRequired = $this->post_check('invite_required', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$maxVerify = $this->post_check('max_verify', PARAM_NULL_EMPTY, PARAM_TYPE_NUMBER);

		in_array($inviteRequired, [CONTEST_ITEM_INVITE_REQUIRED_YES, CONTEST_ITEM_INVITE_REQUIRED_NO]) or
		$inviteRequired = CONTEST_ITEM_INVITE_REQUIRED_NO;

		!is_null($maxStock) and ($maxStock >= 0 or $maxStock = 0);
		$maxStock < CONTEST_ITEM_MAX_STOCK or $maxStock = CONTEST_ITEM_MAX_STOCK;


		// 获取活动项目资料
		$itemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($itemInfo['state'] != CONTEST_ITEM_STATE_OK) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_STATE_INVALID);
		}

		if (empty($maxStock)) {
			$maxStock = $itemInfo['max_stock'];
		}

		if ($inviteRequired == CONTEST_ITEM_INVITE_REQUIRED_YES && empty($maxStock)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEM_MUST_SET_MAX_PLAYER);
		}

		$verify = $this->contestEditVerify($itemInfo['fk_contest'], true);
		if ($verify->error < 0) {
			return $this->response_error($verify->error);
		}

		$params = array(
			'name'            => $name,
			'fee'             => $fee,
			'start_time'      => $startTime,
			'end_time'        => $endTime,
			'invite_required' => $inviteRequired,

			'max_verify' => $maxVerify,
		);

		if (!is_null($maxStock)) {
			$params['max_stock'] = $maxStock;
			$params['cur_stock'] = $maxStock;
		}

		$affected_rows = $this->Contest_model->updateContestItem($itemId, $params);

		//生成报名邀请码
		if ($inviteRequired == CONTEST_ITEM_INVITE_REQUIRED_YES) {
			$this->Msg_model->sendMsgCreateContestItemInviteCode($itemId);
		}

		return $this->response_update($affected_rows);
	}

	/**
	 * 删除项目
	 *
	 */
	public function items_delete_post()
	{
		$itemId = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		// 获取活动项目资料
		$item_info = $this->Contest_model->getContestItemById($itemId);
		if (empty($item_info)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($item_info['state'] != CONTEST_ITEM_STATE_OK) {
			return $this->response_update(0);
		}

		$verify = $this->contestEditVerify($item_info['fk_contest'], true);
		if ($verify->error < 0) {
			return $this->response_error($verify->error);
		}

		$affected_rows = $this->Contest_model->deleteContestItem($itemId);

		return $this->response_update($affected_rows);
	}

	/**
	 * 获取活动活动项目ID
	 *
	 */
	public function items_list_get()
	{
		$cid  = $this->get_check('cid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$page = $this->get_check('page', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);
		$size = $this->get_check('size', PARAM_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$page > 0 or $page = 1;
		$size > 0 or $size = 10;
		$size < 50 or $size = 50;

		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_NOT_EXISTS);
		}

		$items_list = $this->Contest_model->listContestItem($contestInfo['fk_corp'], $cid, $page, $size);

		return $this->response_list($items_list->result, $items_list->total, $page, $size);
	}

	public function items_info_get()
	{
		$itemId = $this->get_check('itemid', PARAM_NOT_NULL_NOT_EMPTY, PARAM_TYPE_NUMBER);

		$item_info = $this->Contest_model->getContestItemById($itemId);
		if (empty($item_info)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		return $this->response_object($item_info);
	}

	public function online_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY);

		// 获取活动资料
		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return Error_Code::ERROR_CONTEST_NOT_EXISTS;
		}

		if ($contestInfo['publish_state'] != CONTEST_PUBLISH_STATE_DRAFT) {
			return Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID;
		}

		$result = $this->Contest_model->onlineContest($cid);

		return $this->response_update($result);
	}

	public function start_selling_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY);

		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($contestInfo['publish_state'] != CONTEST_PUBLISH_STATE_ON) {
			return $this->response_error(Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID);
		}

		$itemList = $this->Contest_model->listContestItem($contestInfo['fk_corp'], $cid, 1, 50);
		if (empty($itemList->total) || empty($itemList->result)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		$itemIds = array();
		foreach ($itemList->result as $v) {
			$itemIds[] = $v['pk_contest_items'];
		}

		$enrolFormList = $this->Form_model->getFormByItemIds($itemIds);
		if (empty($enrolFormList) || count($enrolFormList) != count($itemIds)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEM_ENROL_FORM_NOT_ENOUGH);
		}

		$result = $this->Contest_model->startSellingContest($cid);

		return $this->response_update($result);
	}

	public function offline_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY);

		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($contestInfo['publish_state'] != CONTEST_PUBLISH_STATE_SELLING) {
			return $this->response_error(Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID);
		}

		$result = $this->Contest_model->offlineContest($cid, $contestInfo['publish_state']);

		return $this->response_update($result);
	}

	public function re_online_post()
	{
		$cid = $this->post_check('cid', PARAM_NOT_NULL_NOT_EMPTY);

		$contestInfo = $this->Contest_model->getContestById($cid);
		if (empty($contestInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($contestInfo['publish_state'] != CONTEST_PUBLISH_STATE_OFF) {
			return $this->response_error(Error_Code::ERROR_CONTEST_PUBLISH_STATE_INVALID);
		}

		$result = $this->Contest_model->reOnlineContest($cid);

		return $this->response_update($result);

	}

	public function add_invite_code_post()
	{
		$itemId          = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY);
		$inviteCodeCount = $this->post_check('invite_code_count', PARAM_NOT_NULL_NOT_EMPTY);

		$inviteCodeCount < 100000 or $inviteCodeCount = 100000;

		$itemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		$count = 0;
		for ($j = 0; $j < $inviteCodeCount; $j++) {
			for ($i = 0; $i < 3; $i++) {
				$code = $this->generateCode();
				try {
					$result = $this->Contest_model->addInviteCode($itemId, $code);
					if (empty($result)) {
						continue;
					}
				} catch (Exception $e) {
					$this->catchException($e);
				}
				$count++;
				break;
			}
		}

		return $this->response_object(compact('count'));
	}

	private function generateCode($length = 6)
	{
		$str    = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$slen   = strlen($str) - 1;
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			mt_srand();

			$key = mt_rand(0, $slen);

			$result .= $str[$key];
		}

		return $result;
	}

	public function expire_invite_code_post()
	{
		$itemId       = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY);
		$affectedRows = $this->Contest_model->expireInviteCodes($itemId);

		return $this->response_update($affectedRows);
	}

	public function list_invite_code_get()
	{
		$itemId     = $this->get_check('itemid', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NULL_EMPTY);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 100;
		$pageSize < 1000 or $pageSize = 1000;

		$itemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($itemInfo['invite_required'] == CONTEST_ITEM_INVITE_REQUIRED_NO) {
			return $this->response_list([], 0, $pageNumber, $pageSize);
		}

		$total    = 0;
		$codeList = $this->Contest_model->listInviteCode($itemId, $pageNumber, $pageSize, $total);

		return $this->response_list($codeList, $total, $pageNumber, $pageSize);
	}

	public function get_by_ids_get()
	{
		$contestIds = $this->get_check('cids', PARAM_NOT_NULL_NOT_EMPTY);

		$contestIds = explode(',', $contestIds);

		$contestList = $this->Contest_model->getContestByIds($contestIds);

		$total = count($contestList);

		return $this->response_list($contestList, $total, 1, $total);
	}

	public function get_selling_item_count_by_ids_get()
	{
		$contestIds = $this->get_check('cids', PARAM_NOT_NULL_NOT_EMPTY);

		$contestIds = str_replace(' ', '', $contestIds);
		$contestIds = trim(trim($contestIds), ',');
		$contestIds = implode(',', array_unique(explode(',', $contestIds)));

		$pageNumber = 1;
		$pageSize = 100;

		$itemList = array();
		while (true) {
			$results = $this->Contest_model->getSellingItemByIds($contestIds, $pageNumber, $pageSize);
			if (empty($results)) {
				break;
			}

			$itemList = array_merge($itemList, $results);

			$pageNumber++;
		}

		$result = explode(',', $contestIds);

		$finalList = array();
		foreach ($result as $v) {
			$finalList[$v] = 0;
			foreach ($itemList as $key => $val) {
				if ($v == $val['fk_contest']) {
					if ($val['max_stock'] > 0 && $val['cur_stock'] <= 0) {
						unset($itemList[$key]);
						continue;
					}
					$finalList[$v]++;
					unset($itemList[$key]);
				}
			}
		}
		$total = count($finalList);

		return $this->response_list($finalList, $total, 1, $total);
	}

	public function clear_invite_code_post()
	{
		$itemId = $this->post_check('itemid', PARAM_NOT_NULL_NOT_EMPTY);

		$itemInfo = $this->Contest_model->getContestItemById($itemId);
		if (empty($itemInfo)) {
			return $this->response_error(Error_Code::ERROR_CONTEST_ITEMS_NOT_EXISTS);
		}

		if ($itemInfo['invite_required'] == CONTEST_ITEM_INVITE_REQUIRED_NO) {
			$affectedRows = $this->Contest_model->expireInviteCodeAll($itemId);

			return $this->response_update($affectedRows);
		}

		$total    = 0;
		$lastCode = $this->Contest_model->listInviteCode($itemId, $itemInfo['max_stock'], 1, $total);
		if (empty($lastCode)) {
			return $this->response_update(0);
		}

		$lastCodeId   = $lastCode[0]['pk_enrol_invite_code'];
		$affectedRows = $this->Contest_model->expireInviteCodeExt($itemId, $lastCodeId);

		return $this->response_update($affectedRows);
	}

	/**
	 * 获取核销中的项目
	 */
	public function list_verifying_items_get()
	{
		$fkCorp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date       = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NULL_NOT_EMPTY);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 10;
		$pageSize < 100 or $pageSize = 100;

		$total    = 0;
		$itemList = $this->Contest_model->listVerifyingItems($fkCorp, $date, $pageNumber, $pageSize, $total);
		if (empty($itemList)) {
			return $this->response_list(array(), 0, $pageNumber, $pageSize);
		}

		$this->load->model('Order_model');
		$itemListFinal = array();
		foreach ($itemList as $item) {
			$item['order_count']          = $this->Order_model->getItemOrderCount($item['fk_corp'], $item['fk_contest'], $item['pk_contest_items'])['cnt'];
			$item['order_count_verified'] = $this->Order_model->getItemOrderCount($item['fk_corp'], $item['fk_contest'], $item['pk_contest_items'], $isVerified = true)['cnt'];
			$itemListFinal[]              = $item;
		}
		unset($itemList);

		return $this->response_list($itemListFinal, $total, $pageNumber, $pageSize);
	}
} // END class Contest
