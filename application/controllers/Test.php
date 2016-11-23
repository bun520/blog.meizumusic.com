<?php
/**
 * User: zhaodc
 * Date: 8/30/16
 * Time: 10:10
 */
require_once __DIR__ . '/Base.php';

class Test extends Base
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('Test_model');

	}

	public function index_get()
	{
		$result = $this->Test_model->testSelect();

    }

	public function insert_get()
	{
		$result = $this->Test_model->testInsert();

    }

	public function update_get()
	{
		$result = $this->Test_model->testUpdate();

	}

	public function trans_get()
	{
		$result = $this->Test_model->testTransaction();

	}
}
