<?php defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__ . '/Base.php';
/**
 * Created by PhpStorm.
 * User: yujiaming@wesai.com
 * Date: 2016/7/29
 * Time: 15:49
 */

class Statistics extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model');
        $this->load->model('Contest_model');
    }

    /*
     *
     * 基本信息统计接口
    ***/
    public function get_statistics_info_get()
    {
        $fk_corp     = $this->get_check('fk_corp', PARAM_NOT_NULL_NOT_EMPTY);
        $result =array();
        $result['contest']['yesterday'] = $this->Contest_model->getYesterdayContest($fk_corp)['count(pk_contest)']; //昨天
        $result['contest']['today']     = $this->Contest_model->getTodayContest($fk_corp)['count(pk_contest)']; //今天
        $result['contest']['total']     = $this->Contest_model->getCurrentContest($fk_corp)['count(pk_contest)']; //当前


        $result['order']['yesterday']  = $this->Order_model->getYesterdayOrder($fk_corp)['count(pk_order)']; //昨天
        $result['order']['today']      = $this->Order_model->getTodayOrder($fk_corp)['count(pk_order)']; //今天
        $result['order']['total']    = $this->Order_model->getCurrentOrder($fk_corp)['count(pk_order)']; //当前

        return $this->response_object($result);
    }

}
