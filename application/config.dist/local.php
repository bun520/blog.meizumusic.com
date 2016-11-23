<?php
################################################################
# 框架目录
################################################################
define('SYSTEM_PATH', '/home/zhaodechang/code/open-base/ci/system/');
define('APP_PATH', "/home/zhaodechang/code/open-contest/api/application");
define('WORKSPACE', dirname(dirname(APP_PATH)));
date_default_timezone_set("Asia/Shanghai");

################################################################
# CI ENV
################################################################
$_SERVER['CI_ENV'] = 'development';

################################################################
# DB CONFIG
################################################################
define('CONTEST_DB_CONFIG', 'contestdb');
$DB_CONFIG[CONTEST_DB_CONFIG]['master']     = array(
	'dsn'  => 'mysql:host=10.2.2.5;dbname=open_contest',
	'user' => 'root',
	'pwd'  => 'root',
);
$DB_CONFIG[CONTEST_DB_CONFIG]['slaves'][]   = array(
	'dsn'  => 'mysql:host=10.2.2.5;dbname=open_contest',
	'user' => 'root',
	'pwd'  => 'root',
);
$DB_CONFIG[CONTEST_DB_CONFIG]['persistent'] = false; // 是否启用 PDO 长连接
$DB_CONFIG[CONTEST_DB_CONFIG]['timeout']    = 1; // 数据库操作超时时间，单位（秒）
$DB_CONFIG[CONTEST_DB_CONFIG]['character']  = 'utf8mb4'; // 连接字符集

################################################################
# REDIS CONFIG
################################################################
$REDIS_HOST_CONFIG['redis'] = array(
	'host'       => '127.0.0.1',
	'port'       => 6379,
	'password'   => 'hiwesai',
	'persistent' => false,
	'timeout'    => 1,
	'key_suffix' => '',
);

################################################################
# SPHINX CONFIG
################################################################

define('SPHINX_INDEX_CONTEST_MANAGE', 'ContestManageIndex');
define('SPHINX_INDEX_CONTEST_FRONT', 'ContestFrontIndex');
define('SPHINX_INDEX_ORDER_MANAGE', 'OrderManageIndex');
define('SPHINX_INDEX_TAG_UNITS', 'TagUnitsIndex');

// sphinx config
$SPHINX_HOST_CONFIG['sphinx'] = array(
	SPHINX_INDEX_TAG_UNITS      => array(
		'host'        => '127.0.0.1',
		'port'        => 9312,
		'timeout'     => 10,
		'index'       => SPHINX_INDEX_TAG_UNITS,
		'max_matched' => 50,
	),
	SPHINX_INDEX_CONTEST_FRONT  => array(
		'host'        => '127.0.0.1',
		'port'        => 9312,
		'timeout'     => 10,
		'index'       => SPHINX_INDEX_CONTEST_FRONT,
		'max_matched' => 200,
	),
	SPHINX_INDEX_CONTEST_MANAGE => array(
		'host'        => '127.0.0.1',
		'port'        => 9312,
		'timeout'     => 10,
		'index'       => SPHINX_INDEX_CONTEST_MANAGE,
		'max_matched' => 500,
	),
	SPHINX_INDEX_ORDER_MANAGE   => array(
		'host'        => '127.0.0.1',
		'port'        => 9312,
		'timeout'     => 10,
		'index'       => SPHINX_INDEX_ORDER_MANAGE,
		'max_matched' => 500,
	),
);
