<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ .'/Base.php';

/**
 * 运维服务监控接口
 *
 * @package default
 * @author  : zhaodechang@wesai.com
 **/
class Ops extends Base
{
    public function __construct() {
        parent::__construct();
    }

    public function check_ops_get() {
        $startTime  = microtime(true);
        $std        = new stdClass();
        $std->error = 0;
        $std->mysql = 0;
        $std->redis = 0;

        try {
            $dbConfig = $this->config->item('contestdb');
            $this->load->library('Pdo_Mysql', $dbConfig);
            $sql = 'show tables';
            $this->pdo_mysql
                ->Prepare(Pdo_Mysql::DSN_TYPE_MASTER, $sql)
                ->Execute()
                ->FetchAll($result)
                ->Close();
            // $std->msyql_result = $result;
        } catch (Exception $e) {
            $std->mysql = 1;
        }

        try {
            $redisConfig = $this->config->item('redis');
            $this->load->library('Redis_List_Client', $redisConfig);
            $this->redis_list_client->LeftPush('mq_test_check_ops', 1);
            $redisResult = $this->redis_list_client->RightPop('mq_test_check_ops');
            // $std->redis_result = $redisResult;
        } catch (Exception $e) {
            $std->redis = 1;
        }
        $std->cost = microtime(true) - $startTime;
        exit(json_encode($std));
    }

}
