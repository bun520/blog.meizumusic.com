<?php
require_once __DIR__ . '/Base.php';

/**
 * User: zhaodc
 * Date: 8/10/16
 * Time: 10:58
 */
class Analysis extends Base
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Analysis_model');
	}

	public function get_total_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);

		$total = $this->Analysis_model->getSumNumber($fkCorp);

		return $this->response_object($total);
	}

	public function get_recently_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$days   = $this->get_check('days', PARAM_NULL_NOT_EMPTY);

		$days > 0 or $days = 30;
		$days <= 60 or $days = 60;

		$total = $this->Analysis_model->getSumNumber($fkCorp, $days);

		return $this->response_object($total);

	}

	public function list_daily_get()
	{
		$fkCorp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NULL_NOT_EMPTY);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 20;
		$pageSize < 100 or $pageSize = 100;

		$total     = 0;
		$dailyList = $this->Analysis_model->listDaily($fkCorp, $pageNumber, $pageSize, $total);

		return $this->response_list($dailyList, $total, $pageNumber, $pageSize);
	}


	public function get_contest_count_per_day_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date   = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);

		$count = $this->Analysis_model->getContestCountPerDay($fkCorp, $date);

		$count = $count['cnt'];

		return $this->response_object(compact('count'));
	}

	public function set_calc_data_post()
	{
		$fkCorp        = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date          = $this->post_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$contest_count = $this->post_check('contest_count', PARAM_NULL_EMPTY);
		$item_count    = $this->post_check('item_count', PARAM_NULL_EMPTY);
		$order_count   = $this->post_check('order_count', PARAM_NULL_EMPTY);
		$amount_sum    = $this->post_check('amount_sum', PARAM_NULL_EMPTY);

		$params = compact('contest_count', 'item_count', 'order_count', 'amount_sum');
		$count  = $this->Analysis_model->setCalcData($fkCorp, $date, $params);

		return $this->response_object($count);
	}

	public function get_analysis_contest_by_unq_key_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date   = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);

		$analysisData = $this->Analysis_model->getAnalysisContestByUnqKey($fkCorp, $date);

		return $this->response_object($analysisData);
	}

	public function get_analysis_cursor_get()
	{
		$name = $this->get_check('name', PARAM_NOT_NULL_NOT_EMPTY);

		$cursorInfo = $this->Analysis_model->getAnalysisCursor($name);

		return $this->response_object($cursorInfo);
	}

	public function set_analysis_cursor_get()
	{
		$name  = $this->get_check('name', PARAM_NOT_NULL_NOT_EMPTY);
		$value = $this->get_check('value', PARAM_NOT_NULL_NOT_EMPTY);

		$cursorInfo = $this->Analysis_model->setAnalysisCursor($name, $value);

		return $this->response_update($cursorInfo);
	}

	public function list_order_per_day_get()
	{
		$fkCorp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date       = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NULL_NOT_EMPTY);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 20;
		$pageSize < 100 or $pageSize = 100;

		$cursorInfo = $this->Analysis_model->listOrderPerDay($fkCorp, $date, $pageNumber, $pageSize);

		return $this->response_list($cursorInfo, count($cursorInfo), $pageNumber, $pageSize);
	}

	public function list_contest_item_per_day_get()
	{
		$fkCorp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date       = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$pageNumber = $this->get_check('page', PARAM_NULL_NOT_EMPTY);
		$pageSize   = $this->get_check('size', PARAM_NULL_NOT_EMPTY);

		$pageNumber > 0 or $pageNumber = 1;
		$pageSize > 0 or $pageSize = 20;
		$pageSize < 100 or $pageSize = 100;

		$cursorInfo = $this->Analysis_model->listContestItemPerDay($fkCorp, $date, $pageNumber, $pageSize);

		return $this->response_list($cursorInfo, count($cursorInfo), $pageNumber, $pageSize);
	}

	public function set_order_count_post()
	{
		$fkCorp     = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date       = $this->post_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$cid        = $this->post_check('cid', PARAM_NOT_NULL_EMPTY);
		$itemId     = $this->post_check('item_id', PARAM_NOT_NULL_EMPTY);
		$orderCount = $this->post_check('order_count', PARAM_NOT_NULL_EMPTY);
		$amountSum  = $this->post_check('amount_sum', PARAM_NOT_NULL_EMPTY);

		$count = $this->Analysis_model->setOrderCount($fkCorp, $date, $cid, $itemId, $orderCount, $amountSum);

		return $this->response_object($count);
	}

	public function set_contest_item_count_post()
	{
		$fkCorp    = $this->post_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date      = $this->post_check('date', PARAM_NOT_NULL_NOT_EMPTY);
		$cid       = $this->post_check('cid', PARAM_NOT_NULL_EMPTY);
		$itemCount = $this->post_check('item_count', PARAM_NOT_NULL_EMPTY);

		$count = $this->Analysis_model->setContestItemCount($fkCorp, $date, $cid, $itemCount);

		return $this->response_object($count);
	}

	public function get_contest_item_calc_per_day_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date   = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);

		$itemCalc = $this->Analysis_model->getContestItemCalcPerDay($fkCorp, $date);

		return $this->response_object($itemCalc);
	}

	public function get_order_calc_per_day_get()
	{
		$fkCorp = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
		$date   = $this->get_check('date', PARAM_NOT_NULL_NOT_EMPTY);

		$orderCalc = $this->Analysis_model->getOrderCalcPerDay($fkCorp, $date);

		return $this->response_object($orderCalc);
	}
}
