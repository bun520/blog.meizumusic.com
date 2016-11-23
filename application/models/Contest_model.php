<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed');

require_once __DIR__ . '/ModelBase.php';

/**
 * 活动Model
 *
 * @package : default
 * @author  : zhaodechang@wesai.com
 **/
class Contest_model extends ModelBase
{
	/**
	 * 获取数据库
	 *
	 * @return string : string
	 * @author : zhaodechang@wesai.com
	 */
	public function get_db()
	{
		return CONTEST_DB_CONFIG;
	}

	/**
	 * 新增活动
	 *
	 * @param $params
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function addContest($params)
	{
		try {
			$this->beginTransaction();

			$cBindParams = $params['contest_params'];
			$cSql        = 'insert into t_contest (%s) values (%s)';
			$cColumns    = implode(',', array_keys($cBindParams)) . ', utime';
			$cValues     = ':' . implode(', :', array_keys($cBindParams)) . ', :utime';
			$cSql        = sprintf($cSql, $cColumns, $cValues);

			$cBindParams['utime'] = null;
			$cid                  = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $cSql, $cBindParams);

			switch ($cBindParams['gtype']) {
				case CONTEST_GTYPE_MALATHION:
					$mBindParams = $params['malathion_params'];
					$mSql        = 'insert into t_contest_malathion (%s) values (%s)';
					$mColumns    = implode(',', array_keys($mBindParams)) . ', fk_contest';
					$mValues     = ':' . implode(', :', array_keys($mBindParams)) . ', :fk_contest';
					$mSql        = sprintf($mSql, $mColumns, $mValues);

					$mBindParams['fk_contest'] = $cid;

					$this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $mSql, $mBindParams);
					break;
			}

			$this->commit();

			return $cid;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 根据活动ID获取活动详情
	 *
	 * @param     $pk_contest
	 * @param int $is_return_intro
	 *
	 * @return array|bool : array
	 * @throws \Exception
	 * @author: zhaodechang@wesai.com
	 */
	public function getContestById($pk_contest, $is_return_intro = 0)
	{
		$query = 'select pk_contest, name, ename, gtype, logo, poster, banner, publish_state,
                  sdate, location, source, ctime, utime, fk_corp, fk_corp_user,
                  level, lottery, deliver_gear, country_scope, service_tel %s 
                  from t_contest where pk_contest = :pk_contest';

		$extra_column = '';
		if ($is_return_intro == 1) {
			$extra_column = ', intro';
		}
		$query = sprintf($query, $extra_column);

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('pk_contest'));
	}

	/**
	 * 根据活动ID获取活动详情
	 *
	 * @param     $ids
	 * @param int $returnIntro
	 *
	 * @return array|bool : array
	 * @throws \Exception
	 * @author: zhaodechang@wesai.com
	 */
	public function getContestByIds($ids, $returnIntro = 0)
	{
		$query = 'select pk_contest, name, ename, gtype, logo, poster, banner, publish_state,
                  sdate, location, source, ctime, utime, fk_corp, fk_corp_user,
                  level, lottery, deliver_gear, country_scope, service_tel %s 
                  from t_contest where pk_contest in (%s)';

		$extColumn = '';
		if ($returnIntro == 1) {
			$extColumn = ', intro';
		}

		$cids  = implode(',', $ids);
		$query = sprintf($query, $extColumn, $cids);

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, array());
	}

	public function getSellingItemByIds($ids, $pageNumber, $pageSize)
	{
		$sql = 'select fk_contest, max_stock, cur_stock from t_contest_items where fk_contest in (%s) and state = :state and end_time > :end_time ORDER BY pk_contest_items asc';

		$sql = sprintf($sql, $ids);

		$state = CONTEST_ITEM_STATE_OK;
		$end_time = date('Y-m-d H:i:s');

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('state', 'end_time'), $pageNumber, $pageSize);
	}
	/**
	 * 根据活动ID获取马拉松活动资料
	 *
	 * @param $fk_contest
	 *
	 * @return array|bool : array
	 * @throws \Exception
	 * @author: zhaodechang@wesai.com
	 */
	public function getMalathionById($fk_contest)
	{
		$query = 'select state, show_time, rstart_time,
                  rend_time, gstart_time, gend_time, cstart_time, cend_time, contest_start_time,
                  contest_over_time from t_contest_malathion where fk_contest = :fk_contest';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('fk_contest'));
	}

	/**
	 * 根据活动ID获取马拉松活动资料
	 *
	 * @param $ids
	 *
	 * @return array|bool : array
	 * @throws \Exception
	 * @author: zhaodechang@wesai.com
	 */
	public function getMalathionByIds($ids)
	{
		$query = 'select fk_contest, state, show_time, rstart_time,
                  rend_time, gstart_time, gend_time, cstart_time, cend_time, contest_start_time,
                  contest_over_time from t_contest_malathion where fk_contest in (%s)';

		$fk_contest = implode(',', $ids);

		$query = sprintf($query, $fk_contest);

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, array());
	}

	/**
	 * 更新活动资料
	 *
	 **/
	public function updateContest($pk_contest, $params)
	{
		try {
			$this->beginTransaction();
			$affectedRows = 0;

			$cSql        = 'update t_contest set %s where pk_contest = :pk_contest';
			$cSetColumns = array();
			$cValues     = array();

			foreach ($params['contest_params'] as $key => $value) {
				if (empty($value)) {
					continue;
				}
				$cSetColumns[] = $key . ' = :' . $key;
				$cValues[$key] = $value;
			}

			// 更新马拉松数据
			switch ($params['contest_params']['gtype']) {
				case CONTEST_GTYPE_MALATHION:
					$mSql        = 'update t_contest_malathion set %s where fk_contest = :fk_contest';
					$mSetColumns = array();
					$mValues     = array();
					foreach ($params['malathion_params'] as $key => $value) {
						if (empty($value) && $key != 'level') {
							continue;
						}
						$mSetColumns[] = $key . ' = :' . $key;
						$mValues[$key] = $value;
					}

					// 只处理有数据的参数
					if (count($mSetColumns)) {
						$mSetColumns = implode(',', $mSetColumns);
						$mSql        = sprintf($mSql, $mSetColumns);

						$mValues['fk_contest'] = $pk_contest;

						$affectedRows += $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $mSql, $mValues);

						if ($affectedRows) {
							// 更新活动表utime
							$cSetColumns[]    = 'utime = :utime';
							$cValues['utime'] = null;
						}
					}
					break;
			}

			// 只处理有数据的参数
			if (count($cSetColumns) > 0) {
				$cSetColumns = implode(',', $cSetColumns);
				$cSql        = sprintf($cSql, $cSetColumns);

				$cValues['pk_contest'] = $pk_contest;

				$affectedRows += $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $cSql, $cValues);
			}

			$this->commit();

			return $affectedRows;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 资格审查中
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToReviewing($pk_contest)
	{
		$lottery = MALATHION_LOTTERY_YES;

		return $this->changeMalathionState($pk_contest, MALATHION_STATE_DRAFT, MALATHION_STATE_REVIEWING, __METHOD__, compact('lottery'));
	}

	/**
	 * 马拉松活动状态变更
	 *
	 * @param  integer $pk_contest    活动ID
	 * @param  integer $from_state    起始状态
	 * @param  integer $to_state      目标状态
	 * @param  string  $remark        备注
	 * @param  array   $extra_columns 额外参数
	 *
	 * @return int affect rows
	 * @throws \Exception
	 */
	private function changeMalathionState($pk_contest, $from_state, $to_state, $remark, $extra_columns = null)
	{
		try {
			$this->beginTransaction();

			// 更新活动表utime
			$query_contest = 'update t_contest set utime = :utime where pk_contest = :pk_contest';

			$params               = array();
			$params['utime']      = null;
			$params['pk_contest'] = $pk_contest;
			$this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query_contest, $params);

			// 更新马拉松状态
			$query_malathion = 'update t_contest_malathion set state = :to_state
                                where fk_contest = :fk_contest and state = :from_state';

			$params               = array();
			$params['to_state']   = $to_state;
			$params['from_state'] = $from_state;
			$params['fk_contest'] = $pk_contest;

			$params_log = $params;
			if (!empty($extra_columns) && is_array($extra_columns)) {
				foreach ($extra_columns as $key => $value) {
					if (empty($value)) {
						continue;
					}
					$query_malathion .= ' and ' . $key . ' = :' . $key;
					$params[$key] = $value;
				}
			}
			$affected_rows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $query_malathion, $params);

			// 写马拉松状态变更日志
			$query_malathion_state_log = 'insert into t_contest_malathion_state_log
                                          (fk_contest, from_state, to_state, remark)
                                          values (:fk_contest, :from_state, :to_state, :remark)';
			$params_log['remark']      = $remark;
			$this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query_malathion_state_log, $params_log);

			$this->commit();


			return $affected_rows;
		} catch (Exception $e) {
			$this->rollBack();


			$this->logException($e);
			throw $e;
		}
	}

	/**
	 * 抽签中
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToBalloting($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_REVIEWING, MALATHION_STATE_BALLOTING, __METHOD__);
	}

	/**
	 * 抽签结束
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToBallotCompleted($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_BALLOTING, MALATHION_STATE_BALLOT_COMPLETE, __METHOD__);
	}

	/**
	 * 抽签结束到装备领取中
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateFromBallotCompletedToReceiving($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_BALLOT_COMPLETE, MALATHION_STATE_RECEIVING, __METHOD__);
	}

	/**
	 * 装备领取完成
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToReceiveCompleted($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_RECEIVING, MALATHION_STATE_RECEIVE_COMPLETE, __METHOD__);
	}

	/**
	 * 检录中
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToRollcall($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_RECEIVE_COMPLETE, MALATHION_STATE_ROLL_CALL_START, __METHOD__);
	}

	/**
	 * 检录完成
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToRollcallCompleted($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_ROLL_CALL_START, MALATHION_STATE_ROLL_CALL_END, __METHOD__);
	}

	/**
	 * 竞赛开始
	 *
	 **/
	public function changeMalathionStateToContestStart($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_ROLL_CALL_END, MALATHION_STATE_CONTEST_START, __METHOD__);
	}

	/**
	 * 竞赛结束
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateToContestOver($pk_contest)
	{
		return $this->changeMalathionState($pk_contest, MALATHION_STATE_CONTEST_START, MALATHION_STATE_CONTEST_OVER, __METHOD__);
	}

	/**
	 * 暂存到装备领取中
	 *
	 * @param $pk_contest
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function changeMalathionStateFromDraftToReceiving($pk_contest)
	{
		$lottery = MALATHION_LOTTERY_NO;

		return $this->changeMalathionState($pk_contest, MALATHION_STATE_DRAFT, MALATHION_STATE_RECEIVING, __METHOD__, compact('lottery'));
	}


	/**
	 * 删除活动所有地理位置关联
	 *
	 * @param  integer $fk_contest 活动ID
	 *
	 * @return mixed
	 */
	public function deleteLocationTag($fk_contest)
	{
		$query = 'delete from t_mapping_contest_location where fk_contest = :fk_contest';

		return $this->delete(Pdo_Mysql::DSN_TYPE_MASTER, $query, compact('fk_contest'));
	}

	/**
	 * 新增活动地理位置
	 *
	 * @param integer $fk_contest 活动ID
	 * @param string  $tag        标签名称
	 * @param integer $level      行政区划级别
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function addLocationTag($fk_contest, $tag, $level)
	{
		// 获取tag主键
		$pk_tag_location = $this->upsertTagLocation($tag, $level);
		if (false === $pk_tag_location) {
			return false;
		}

		// 新增活动地理位置关联
		$query = 'insert into t_mapping_contest_location (fk_tag_location, fk_contest, rd_level) 
				  values (:fk_tag_location, :fk_contest, :rd_level)';

		$params                    = array();
		$params['fk_tag_location'] = $pk_tag_location;
		$params['fk_contest']      = $fk_contest;
		$params['rd_level']        = $level;

		return $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);
	}

	/**
	 * 获取tag主键
	 *
	 * @param  string  $name  tag名称
	 * @param  integer $level 行政区划级别
	 *
	 * @return \pk_tag_location
	 * @throws \Exception
	 */
	public function upsertTagLocation($name, $level = null)
	{
		$tag_info = $this->getTagLocationByName(Pdo_Mysql::DSN_TYPE_MASTER, $name, $level);

		// tag不存在，新增
		if (empty($tag_info)) {
			$query = 'insert into t_tag_location (name, state, level, utime) 
					  values (:name, :state, :level, :utime)';

			$params          = array();
			$params['name']  = $name;
			$params['state'] = TAG_STATE_OK;
			$params['level'] = $level;
			$params['utime'] = null;

			return $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);
		}

		// tag已存在，但是无效
		if ($tag_info['state'] == TAG_STATE_NG) {
			log_message('error', 'location tag state illegal');

			return false;
		}

		return $tag_info['pk_tag_location'];
	}

	/**
	 * 查询tag资料
	 *
	 * @param  string  $dsn_type 一般读从库
	 * @param  string  $name     位置名称
	 * @param  integer $level    行政区划级别 1-国家，2-省／直辖市，3-地级市／区
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getTagLocationByName($dsn_type, $name, $level = null)
	{
		$query  = 'select pk_tag_location, name, state, level from t_tag_location where name = :name ';
		$params = compact('name');
		if (!empty($level)) {
			$query .= ' and level = :level';
			$params['level'] = $level;
		}
		$tag_info = $this->getSingle($dsn_type, $query, $params);

		return $tag_info;
	}

	/**
	 * 为活动添加组织单位标签
	 *
	 * @param integer $fk_contest 活动ID
	 * @param string  $tag        组织单位标签名称
	 * @param integer $role       组织单位角色
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function addUnitsTag($fk_contest, $tag, $role)
	{
		$pk_tag_units = $this->upsertTagUnits($tag);
		if (false === $pk_tag_units) {
			return false;
		}

		$query = 'insert into t_mapping_contest_units (fk_tag_units, fk_contest, role) 
				  values (:fk_tag_units, :fk_contest, :role)';

		$params                 = array();
		$params['fk_tag_units'] = $pk_tag_units;
		$params['fk_contest']   = $fk_contest;
		$params['role']         = $role;

		return $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);
	}

	/**
	 * 新增组织单位标签
	 *
	 * @param  string $tag 标签名称
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function upsertTagUnits($tag)
	{
		$tag_info = $this->getTagUnitsByName(Pdo_Mysql::DSN_TYPE_MASTER, $tag);
		if (empty($tag_info)) {
			$query = 'insert into t_tag_units (name, state, utime) values (:name, :state, :utime)';

			$params          = array();
			$params['name']  = $tag;
			$params['state'] = TAG_STATE_OK;
			$params['utime'] = null;

			return $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $query, $params);
		}

		if ($tag_info['state'] == TAG_STATE_NG) {
			log_message('error', 'units tag state invalid');

			return false;
		}

		return $tag_info['pk_tag_units'];
	}

	/**
	 * 根据tag名称获取组织单位标签
	 *
	 * @param  string $dsn_type
	 * @param  string $name 标签名称
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getTagUnitsByName($dsn_type, $name)
	{
		$query = 'select pk_tag_units, name, state from t_tag_units where name = :name';

		return $this->getSingle($dsn_type, $query, compact('name'));
	}

	/**
	 * 根据tag ID 获取详情
	 *
	 * @param  integer $pk_tag_units tag ID
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getTagUnitsById($pk_tag_units)
	{
		$query = 'select pk_tag_units, name, state from t_tag_units where pk_tag_units = :pk_tag_units';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('pk_tag_units'));
	}

	/**
	 * 根据tag ID 获取详情
	 *
	 * @param  integer $pk_tag_units tag ID
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getTagUnitByIds($pk_tag_units)
	{
		$query = 'select pk_tag_units, name, state from t_tag_units where pk_tag_units in (%s)';
		$query = sprintf($query, implode(',', $pk_tag_units));

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, array());
	}

	/**
	 * 获取活动地理位置列表
	 *
	 * @param  integer $fk_contest 活动ID
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function listContestTagLocation($fk_contest)
	{
		$query = 'select B.pk_tag_location, B.name, A.rd_level as level
                  from t_mapping_contest_location as A, t_tag_location as B
                  where A.fk_tag_location = B.pk_tag_location and A.fk_contest = :fk_contest';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('fk_contest'));
	}

	/**
	 * 获取活动组织单位列表
	 *
	 * @param  integer $fk_contest 活动ID
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function listContestTagUnits($fk_contest)
	{
		$query = 'select B.pk_tag_units, B.name, A.role
                  from t_mapping_contest_units as A, t_tag_units as B
                  where A.fk_tag_units = B.pk_tag_units and A.fk_contest = :fk_contest';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('fk_contest'));
	}

	public function listTagUnits($pageNumber, $pageSize)
	{
		$query = 'select pk_tag_units, name from t_tag_units where state = 1 order BY pk_tag_units asc';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, array(), $pageNumber, $pageSize);
	}

	/**
	 * sphinx筛选组织单位
	 *
	 * @param  string  $name 组织单位名称
	 * @param  integer $page 页面
	 * @param  integer $size 页长
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function searchTagUnits($name, $page = 1, $size = 20)
	{
		$config_name = 'tag_units';
		$sph_config  = $this->config->item('sphinx')[$config_name];
		$this->load->helper('sphinx');
		$this->sphinxclient = sphinx_init_helper($sph_config);
		$this->sphinxclient->SetLimits(($page - 1) * $size, $size, $sph_config['max_matched']);

		$query = '';
		if (!empty($name)) {
			$query = '@name ' . $name;
		}

		$result = $this->sphinxclient->Query($query, $sph_config['index']);
		$ids    = array();
		if (!empty($result['matches'])) {
			foreach ($result['matches'] as $key => $value) {
				$ids[] = $value['id'];
			}
		}

		$std         = new stdClass();
		$std->total  = intval($result['total']);
		$std->result = $ids;

		return $std;
	}

	/**
	 * 筛选活动front
	 *
	 * @param  string  $name      活动名称（中文＋英文）
	 * @param  array   $location  第三级地理位置行政区划tag ID
	 * @param  integer $gtype     竞赛类型
	 * @param  integer $sdate_min 竞赛时间范围起始
	 * @param  integer $sdate_max 竞赛时间范围终止
	 * @param  integer $page      页码
	 * @param  integer $size      页长
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function searchContestFront($fk_corp, $name = '', $location = array(), $gtype = 0, $sdate_min = 0, $sdate_max = 0, $page = 1, $size = 10)
	{
		$config_name = SPHINX_INDEX_CONTEST_FRONT;
		$sph_config  = $this->config->item('sphinx')[$config_name];
		$this->load->helper('sphinx');
		$sphinxClient = sphinx_init_helper($sph_config);
		$sphinxClient->SetSortMode(SPH_SORT_EXTENDED, 'ctime DESC');
		$sphinxClient->SetLimits(($page - 1) * $size, $size, $sph_config['max_matched']);
		$query = '';

		$sphinxClient->SetFilter('fk_corp', compact('fk_corp'));

		if (!empty($name)) {
			$query = '@name ' . $name;
		}
		if (!empty($location)) {
			$sphinxClient->SetFilter('location', $location);
		}
		if (!empty($gtype)) {
			$sphinxClient->SetFilter('gtype', compact('gtype'));
		}
		if ((!empty($sdate_min) || !empty($sdate_max)) && $sdate_min >= 0 && $sdate_max >= 0 && ($sdate_min <= $sdate_max)) {
			$sphinxClient->SetFilterRange('ctime', $sdate_min, $sdate_max);
		}

		$result = $sphinxClient->Query($query, $sph_config['index']);
		$ids    = array();
		if (!empty($result['matches'])) {
			foreach ($result['matches'] as $key => $value) {
				$ids[] = $value['id'];
			}
		}

		$std         = new stdClass();
		$std->total  = intval($result['total']);
		$std->result = $ids;

		return $std;
	}

	/**
	 * 筛选活动manage
	 *
	 * @param  string  $name  活动名称（中文＋英文）
	 * @param  array   $state 马拉松活动状态数组
	 * @param  integer $gtype 竞赛类型
	 * @param  integer $page  页码
	 * @param  integer $size  页长
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function searchContestManage($fk_corp, $name = '', $state = array(), $gtype = 0, $page = 1, $size = 10, $minDate, $maxDate)
	{
		$config_name = SPHINX_INDEX_CONTEST_MANAGE;
		$sph_config  = $this->config->item('sphinx')[$config_name];
		$this->load->helper('sphinx');
		$sphinxClient = sphinx_init_helper($sph_config);
		$sphinxClient->SetSortMode(SPH_SORT_EXTENDED, 'ctime DESC');
		$sphinxClient->SetLimits(($page - 1) * $size, $size, $sph_config['max_matched']);
		$query = '';

		$sphinxClient->SetFilter('fk_corp', compact('fk_corp'));

		if (!empty($name)) {
			$query = '@name ' . $name;
		}
		if (!empty($state)) {
			$sphinxClient->SetFilter('state', $state);
		}
		if (!empty($gtype)) {
			$sphinxClient->SetFilter('gtype', compact('gtype'));
		}
		if (!empty($minDate) || !empty($maxDate) && $minDate <= $maxDate) {
			$sphinxClient->SetFilterRange('ctime', $minDate, $maxDate);
		}
		$result = $sphinxClient->Query($query, $sph_config['index']);
		$ids    = array();
		if (!empty($result['matches'])) {
			foreach ($result['matches'] as $key => $value) {
				$ids[] = $value['id'];
			}
		}

		$std         = new stdClass();
		$std->total  = intval($result['total']);
		$std->result = $ids;

		return $std;
	}

	/**
	 * 新增活动项目
	 *
	 * @param array $params 参数列表
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function addContestItem($params)
	{

		$params['utime'] = null;

		$exceptKeys = ['utime', 'fee'];

		return $this->mixedInsertData('t_contest_items', $params, $exceptKeys);
	}

	/**
	 * 更新活动项目
	 *
	 * @param  integer $pk_contest_items 活动项目ID
	 * @param  array   $params           参数列表
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function updateContestItem($pk_contest_items, $params)
	{
		$conditions = compact('pk_contest_items');
		$exceptKeys = ['cur_stock', 'max_stock'];

		return $this->mixedUpdateData('t_contest_items', $params, $conditions, $exceptKeys);
	}

	public function deleteContestItem($itemid)
	{
		$params     = ['state' => CONTEST_ITEM_STATE_NG];
		$conditions = array(
			'pk_contest_items' => $itemid,
			'state'            => CONTEST_ITEM_STATE_OK,
		);

		return $this->mixedUpdateData('t_contest_items', $params, $conditions);
	}

	/**
	 * 根据项目ID获取活动项目资料
	 *
	 * @param  integer $pk_contest_items 活动项目ID
	 * @param string   $dsn_type
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function getContestItemById($pk_contest_items, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'select pk_contest_items, fk_contest, name, max_stock, 
				  cur_stock, fee, start_time, end_time, state, invite_required, max_verify
                  from t_contest_items where pk_contest_items = :pk_contest_items';

		return $this->getSingle($dsn_type, $query, compact('pk_contest_items'));
	}

	/**
	 * 根据项目ID获取活动项目资料
	 *
	 * @param        $itemids
	 * @param string $dsn_type
	 *
	 * @return array|bool
	 * @throws \Exception
	 * @internal param int $pk_contest_items 活动项目ID
	 */
	public function getContestItemByItemIds($itemids, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'select pk_contest_items, fk_contest, name, max_stock,
                  cur_stock, fee, start_time, end_time, state, invite_required, max_verify
                  from t_contest_items where pk_contest_items in (%s)';

		$ids   = implode(',', $itemids);
		$query = sprintf($query, $ids);

		return $this->getAll($dsn_type, $query, array(), 1, count($itemids));
	}

	/**
	 * 获取活动活动项目列表
	 *
	 * @param  integer $fk_contest 活动ID
	 * @param  integer $page       页码
	 * @param  integer $size       页长
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function listContestItem($fk_corp, $fk_contest, $page = 1, $size = 10)
	{
		$state = CONTEST_ITEM_STATE_OK;
		$rs    = new stdClass();

		$query = 'select count(pk_contest_items) as count from t_contest_items where fk_corp=:fk_corp and fk_contest = :fk_contest and state = :state';

		$t = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('fk_corp', 'fk_contest', 'state'));

		$rs->total = $t['count'];

		$query = 'select pk_contest_items, fk_contest, name, max_stock, 
				  cur_stock, fee, start_time, end_time, state, invite_required, max_verify
                  from t_contest_items where fk_corp = :fk_corp and fk_contest = :fk_contest and state = :state order by ctime DESC';

		$result = $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, compact('fk_corp', 'fk_contest', 'state'), $page, $size);

		$rs->result = $result;

		return $rs;
	}

	public function onlineContest($cid)
	{
		return $this->changeContestPublishState($cid, CONTEST_PUBLISH_STATE_ON, CONTEST_PUBLISH_STATE_DRAFT, __METHOD__);
	}

	private function changeContestPublishState($contestId, $toState, $fromState, $remark = null)
	{
		try {

			$this->beginTransaction();

			$params = array(
				'publish_state' => $toState,
			);

			$conditions = array(
				'pk_contest'    => $contestId,
				'publish_state' => $fromState,
			);

			$sqlData = $this->makeUpdateSqlData('t_contest', $params, $conditions);
			if (empty($sqlData)) {
				$this->rollBack();
				$errMsg = array(
					'msg'        => 'makeUpdateSqlData failed',
					'params'     => $params,
					'conditions' => $conditions,
				);
				log_message_v2('error', $errMsg);

				return false;
			}

			$affectedRows = $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);
			if (empty($affectedRows)) {
				$this->rollBack();
				$errMsg = array(
					'msg'        => $remark . ' failed, update contest publish_state',
					'params'     => $params,
					'conditions' => $conditions,
				);
				log_message_v2('error', $errMsg);

				return false;
			}

			$params = array(
				'fk_contest' => $contestId,
				'from_state' => $toState,
				'to_state'   => $fromState,
				'remark'     => $remark,
			);

			$sqlData = $this->makeInsertSqlData('t_contest_state_log', $params);
			if (empty($sqlData)) {
				$this->rollBack();
				$errMsg = array(
					'msg'    => 'makeInsertSqlData ',
					'params' => $params,
				);
				log_message_v2('error', $errMsg);

				return false;
			}

			$logId = $this->insert(Pdo_Mysql::DSN_TYPE_MASTER, $sqlData['sql'], $sqlData['bindParams']);
			if (empty($logId)) {
				$this->rollBack();
				$errMsg = array(
					'msg'    => 'write contest state log failed',
					'params' => $params,
				);
				log_message_v2('error', $errMsg);

				return false;
			}

			$this->commit();

			$affectedRows++;

			return $affectedRows;
		} catch (Exception $e) {
			$this->rollBack();

			$this->logException($e);
			throw $e;
		}
	}

	public function reOnlineContest($cid)
	{
		return $this->changeContestPublishState($cid, CONTEST_PUBLISH_STATE_ON, CONTEST_PUBLISH_STATE_OFF, __METHOD__);
	}

	public function startSellingContest($cid)
	{
		return $this->changeContestPublishState($cid, CONTEST_PUBLISH_STATE_SELLING, CONTEST_PUBLISH_STATE_ON, __METHOD__);
	}

	public function offlineContest($cid, $fromState)
	{
		return $this->changeContestPublishState($cid, CONTEST_PUBLISH_STATE_OFF, $fromState, __METHOD__);
	}

	public function listContest($pageNumber = 1, $pageSize = 20, $visible = null, $fk_corp = null)
	{
		$query = 'select pk_contest, fk_corp, fk_corp_user, name, ename, gtype, sdate, ctime, level, lottery, deliver_gear,
 				  publish_state, country_scope, service_tel, source from t_contest %s ORDER BY pk_contest asc';

		$params = array();

		$sWhere = ' where 1 = 1 ';
		switch ($visible) {
			case 1: // 仅筛选可见内容
				$sWhere .= ' and publish_state in(' . CONTEST_PUBLISH_STATE_ON . ',' . CONTEST_PUBLISH_STATE_SELLING . ') ';
				break;
			default:
				$sWhere .= '';
				break;
		}

		if (!empty($fk_corp)) {
			$sWhere .= ' and fk_corp = :fk_corp ';
			$params['fk_corp'] = $fk_corp;
		}

		$query = sprintf($query, $sWhere);

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $query, $params, $pageNumber, $pageSize);
	}

	public function addInviteCode($itemId, $code)
	{
		$params = array(
			'fk_contest_items' => $itemId,
			'invite_code'      => $code,
			'utime'            => null,
		);

		$exceptKeys = array('utime');

		return $this->mixedInsertData('t_enrol_invite_code', $params, $exceptKeys);
	}

	public function useInviteCode($itemId, $code, $oid)
	{
		$params = array(
			'state'    => CONTEST_ITEM_INVITE_CODE_STATE_USED,
			'fk_order' => $oid,
		);

		$conditions = array(
			'state'            => CONTEST_ITEM_INVITE_CODE_STATE_UNUSED,
			'invite_code'      => $code,
			'fk_contest_items' => $itemId,
		);

		return $this->mixedUpdateData('t_enrol_invite_code', $params, $conditions);
	}

	public function expireInviteCodeAll($itemId)
	{
		$params = array(
			'state' => CONTEST_ITEM_INVITE_CODE_STATE_EXPIRED,
		);

		$conditions = array(
			'state'            => CONTEST_ITEM_INVITE_CODE_STATE_UNUSED,
			'fk_contest_items' => $itemId,
		);

		return $this->mixedUpdateData('t_enrol_invite_code', $params, $conditions);
	}

	public function expireInviteCodeExt($itemId, $lastCodeId)
	{
		$sql = 'update t_enrol_invite_code set state = :toState where fk_contest_items = :itemId and state = :fromState and pk_enrol_invite_code > :lastCodeId';

		$params = array(
			'toState'    => CONTEST_ITEM_INVITE_CODE_STATE_EXPIRED,
			'fromState'  => CONTEST_ITEM_INVITE_CODE_STATE_UNUSED,
			'itemId'     => $itemId,
			'lastCodeId' => $lastCodeId,
		);

		return $this->update(Pdo_Mysql::DSN_TYPE_MASTER, $sql, $params);
	}

	public function getInviteCodeByCode($itemId, $code)
	{
		$sql = 'select * from t_enrol_invite_code where fk_contest_items = :itemId and invite_code = :code';

		return $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('itemId', 'code'));
	}

	public function listInviteCode($itemId, $pageNumber, $pageSize, &$total)
	{
		$state = CONTEST_ITEM_INVITE_CODE_STATE_UNUSED;
		$sql   = 'select count(*) as cnt from t_enrol_invite_code where fk_contest_items = :itemId and state = :state';

		$result = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('itemId', 'state'));

		$total = $result['cnt'];

		$sql = 'select pk_enrol_invite_code, fk_contest_items, invite_code, fk_order, state as cnt from t_enrol_invite_code 
				  where fk_contest_items = :itemId and state = :state order by pk_enrol_invite_code asc';

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, compact('itemId', 'state'), $pageNumber, $pageSize);
	}

	public function getYesterdayContest($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$ytime = time() - 24 * 60 * 60;
		$atime = date('Y-m-d 00:00:00', $ytime);
		$ztime = date('Y-m-d 23:59:59', $ytime);
		$query = 'SELECT count(pk_contest)  FROM t_contest WHERE fk_corp = :fk_corp AND  ctime > :atime AND ctime < :ztime  ';

		return $this->getSingle($dsn_type, $query, compact('atime', 'ztime', 'fk_corp'));
	}

	//查询活动数量

	public function getTodayContest($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$ytime = time();
		$atime = date('Y-m-d 00:00:00', $ytime);
		$ztime = date('Y-m-d 23:59:59', $ytime);
		$query = 'SELECT count(pk_contest)  FROM t_contest WHERE fk_corp = :fk_corp AND  ctime > :atime AND ctime < :ztime  ';

		return $this->getSingle($dsn_type, $query, compact('atime', 'ztime', 'fk_corp'));
	}

	public function getCurrentContest($fk_corp, $dsn_type = Pdo_Mysql::DSN_TYPE_SLAVE)
	{
		$query = 'SELECT count(pk_contest) FROM t_contest WHERE fk_corp = :fk_corp';

		return $this->getSingle($dsn_type, $query, compact('fk_corp'));
	}

	public function listVerifyingItems($fkCorp, $date, $pageNumber, $pageSize, &$total)
	{
		$sqlSuffix = ' from ' . $this->tableNameContestItem . ' as A inner join  ' . $this->tableNameContest . ' as B on (A.fk_contest = B.pk_contest) where A.fk_corp = :fkCorp and A.start_time > :fromTime and A.start_time <= :toTime and A.state = :state';

		$fromTime = date('Y-m-d 00:00:00', strtotime($date));
		$toTime   = date('Y-m-d 23:59:59', strtotime($date));
		$state    = CONTEST_ITEM_STATE_OK;
		$params   = compact('fkCorp', 'fromTime', 'toTime', 'state');

		$sql = 'select count(*) as cnt ' . $sqlSuffix;

		$count = $this->getSingle(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params);
		$total = $count['cnt'];

		$sql = 'select A.*, B.name as cname, B.ename as cename, B.banner ' . $sqlSuffix ;

		return $this->getAll(Pdo_Mysql::DSN_TYPE_SLAVE, $sql, $params, $pageNumber, $pageSize);
	}

	public function expireInviteCodes()
	{
	}

	private function placeholders($text, $count = 0, $separator = ",")
	{
		$result = array();
		if ($count > 0) {
			for ($x = 0; $x < $count; $x++) {
				$result[] = $text;
			}
		}

		return implode($separator, $result);
	}
} // END class

