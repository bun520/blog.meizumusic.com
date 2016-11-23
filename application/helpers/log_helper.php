<?php
/**
 * User: zhaodc
 * Date: 16/6/25
 * Time: 上午11:56
 */

if (!function_exists('log_message_v2')) {
	function log_message_v2($level, $msg)
	{
		if (is_array($msg) || is_object($msg)) {
			$msg = json_encode($msg);
		}
		log_message($level, $msg);
	}
}
