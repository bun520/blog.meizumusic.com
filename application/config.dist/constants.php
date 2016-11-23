<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', true);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE') OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ') OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESCTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS') OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code


/**
 * 竞赛类型
 **/
//默认值
define('CONTEST_GTYPE_DEFAULT', 1);
// 马拉松
define('CONTEST_GTYPE_MALATHION', 2);

/**
 * 活动上架状态
 */
//暂存
define('CONTEST_PUBLISH_STATE_DRAFT', 1);
//上架
define('CONTEST_PUBLISH_STATE_ON', 2);
//开始售卖
define('CONTEST_PUBLISH_STATE_SELLING', 3);
//下架
define('CONTEST_PUBLISH_STATE_OFF', 4);

/**
 * 活动认证级别
 **/
// 默认0
define('MALATHION_LEVEL_DEFAULT', 0);

/**
 * 马拉松状态
 **/
// 暂存
define('MALATHION_STATE_DRAFT', 1);
// 资格审核中
define('MALATHION_STATE_REVIEWING', 2);
// 抽签中
define('MALATHION_STATE_BALLOTING', 3);
// 抽签结束
define('MALATHION_STATE_BALLOT_COMPLETE', 4);
// 装备领取中
define('MALATHION_STATE_RECEIVING', 5);
// 装备领取完成
define('MALATHION_STATE_RECEIVE_COMPLETE', 6);
// 检录中
define('MALATHION_STATE_ROLL_CALL_START', 7);
// 检录完成
define('MALATHION_STATE_ROLL_CALL_END', 8);
// 竞赛开始
define('MALATHION_STATE_CONTEST_START', 9);
// 竞赛结束
define('MALATHION_STATE_CONTEST_OVER', 10);

/**
 * 马拉松比赛是否需要抽签
 **/
// 需要抽签
define('MALATHION_LOTTERY_YES', 1);
// 不需要抽签
define('MALATHION_LOTTERY_NO', 2);


/**
 * REDIS TOPICS
 **/
// 马拉松状态变更队列
define('MQ_TOPIC_MALATHION_STATE_CHANGE', 'contest_mq_malathion_state_change');
//订单支付完成处理队列
define('MQ_TOPIC_ORDER_PAY_COMPLETED', 'contest_mq_order_pay_completed');
// 来自微信的活动文件上传队列
define('MQ_TOPIC_WX_FILE_UPLOAD', 'contest_mq_wx_file_upload');
// 活动订单导出
define('MQ_TOPIC_CONTEST_ORDER_EXPORT', 'contest_mq_order_export');
//生成报名邀请码
define('MQ_TOPIC_CREATE_CONTEST_ITEM_INVITE_CODE', 'contest_mq_create_item_invite_code');

/**
 * 标签状态
 **/
// 正常
define('TAG_STATE_OK', 1);
// 无效
define('TAG_STATE_NG', 2);

/**
 * 活动项目状态
 **/
// 正常
define('CONTEST_ITEM_STATE_OK', 1);
// 已删除
define('CONTEST_ITEM_STATE_NG', 2);


/**
 * 订单状态
 **/
// 初始化
define('ORDER_STATE_INIT', 1);
// 支付中
define('ORDER_STATE_PAYING', 2);
// 支付失败
define('ORDER_STATE_FAILED', 3);
// 支付完成
define('ORDER_STATE_COMPLETED', 4);
// 订单关闭
define('ORDER_STATE_CLOSED', 5);
// 退款中
define('ORDER_STATE_REFUNDING', 6);
// 退款失败
define('ORDER_STATE_REFUND_FAILED', 7);
// 退款完成
define('ORDER_STATE_REFUND_COMPLETED', 8);

// 抽签状态
define('ORDER_LOTTERY_STATE_INIT', 0);
// 抽签中
define('ORDER_LOTTERY_STATE_START', 1);
// 中签
define('ORDER_LOTTERY_STATE_SUCCESS', 2);
// 未中签
define('ORDER_LOTTERY_STATE_FAILED', 3);

/**
 * 核销状态
 */
// 初始化
define('ORDER_VERIFY_STATE_INIT', 0);
// 未核销
define('ORDER_VERIFY_STATE_UNVERIFIED', 1);
// 已核销
define('ORDER_VERIFY_STATE_VERIFIED', 2);

/**
 * 活动项目参赛人数是否有上限
 **/
// 活动项目参赛人数有上限
define('CONTEST_ITEMS_HAS_QUOTA_YES', 1);
// 活动项目参赛人数无上限
define('CONTEST_ITEMS_HAS_QUOTA_NO', 2);

// 订单渠道
// 微赛
define('ORDER_CHANNEL_WESAI', 1);
// 微票儿
define('ORDER_CHANNEL_WEPIAO', 2);

//支付通道配置
//微信支付 JSAPI
define('ORDER_PAY_CHANNEL_WEIXIN_JSAPI', 1);
// 微信支付 APP
define('ORDER_PAY_CHANNEL_WEIXIN_APP', 2);
//支付宝支付 APP
define('ORDER_PAY_CHANNEL_ALIPAY_APP', 3);
// 支付宝支付 WAP
define('ORDER_PAY_CHANNEL_ALIPAY_WAP', 4);
// 微信支付 二维码
define('ORDER_PAY_CHANNEL_WEIXIN_NATIVE', 5);

//订单来源
//微信公众号
define('ORDER_SOURCE_WEIXIN', 1);
//微赛APP
define('ORDER_SOURCE_APP', 2);
//M站
define('ORDER_SOURCE_MSITE', 3);

// 活动来源
// 智慧体育
define('CONTEST_SOURCE_WISDOM', 1);
// 微赛
define('CONTEST_SOURCE_WESAI', 2);

// 活动地理范围
// 国内
define('CONTEST_COUNTRY_INTERNAL', 1);
// 国外
define('CONTEST_COUNTRY_ABROAD', 2);

// 活动个性化短信模板
// 报名成功
define('CONTEST_ENROL_SUCCESS', 1);
// 领取装备
define('CONTEST_PACKAGE_RECEIVE', 2);


// 订单超时时间
define('ORDER_TIME_EXPIRE', 10); // 分钟

// 表单项目是否有效
define('ENROL_FORM_ITEM_STATE_OK', 1);
define('ENROL_FORM_ITEM_STATE_NG', 2);

/**
 * 活动项目是否需要邀请报名
 */
define('CONTEST_ITEM_INVITE_REQUIRED_YES', 1);
define('CONTEST_ITEM_INVITE_REQUIRED_NO', 2);

/**
 * 报名邀请码状态
 */
//未使用
define('CONTEST_ITEM_INVITE_CODE_STATE_UNUSED', 1);
//已使用
define('CONTEST_ITEM_INVITE_CODE_STATE_USED', 2);
//已过期
define('CONTEST_ITEM_INVITE_CODE_STATE_EXPIRED', 3);

define('CONTEST_ITEM_MAX_STOCK', 100000);

// 订单超时时间
define('ORDER_PAY_TIME_LIMIT', 11);
