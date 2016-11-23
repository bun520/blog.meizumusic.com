<?php

/**
 * User: zhaodc
 * Date: 8/30/16
 * Time: 10:09
 */
class Test_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct();

		//设定数据库配置, application/config/local.php 中定义
		$this->setDBConfig(CONTEST_DB_CONFIG);
	}

	public function testSelect()
	{

		$result = $this->setTable('test')// 查询目标table
		               ->addQueryFields('pk_test,ctime')// 设定查询字段 (字符串 ','分割)
		               ->addQueryFields(array('name'))// 设定查询字段 (数组)
		               ->addQueryConditions('pk_test', 1, '=')//设定一个查询条件
		               ->addQueryConditions(
									array(
										array('pk_test', 1, '='),
										array('ctime', date('Y-m-d'), '<'),
									)
								)//设定一组查询条件
		               ->addOrderBy('ctime', 'desc')// 设定一个排序规则
		               ->addOrderBy(array('ctime' => 'desc'))//设定一组排序规则
		               ->addGroupBy('name')//设定分组
		               ->doSelect(1, 2); // pageNumber, pageSize

		print_r($result);
	}

	public function testInsert()
	{
		$result = $this->setTable('test')
		               ->addInsertColumns(array('name' => 1))//设定插入字段
		               ->doInsert();


		print_r($result);

		$result = $this->addOrderBy(array('ctime' => 'desc'))
		               ->doSelect(1, 5);

		print_r($result);
	}

	public function testUpdate()
	{
		$result = $this->setTable('test')
		               ->addUpdateColumns('name', 2)//设定一个更新字段
		               ->addUpdateColumns(array(array('name', 3, '+')))//设定一组更新字段
		               ->addQueryConditions('pk_test', 1)//设定一个查询条件
		               ->doUpdate();

		print_r($result);

		$result = $this->setTable('test')
		               ->addQueryConditions('pk_test', 1)
		               ->doSelect();

		var_dump($result);
	}

	public function testTransaction()
	{
		try {
			$this->beginTransaction(); //启动事物处理

			$this->testInsert();

			$this->testUpdate();

			$this->commit(); //提交事物
		} catch (Exception $e) {
			$this->rollBack();//回滚事物
			throw $e;
		}
	}

}
