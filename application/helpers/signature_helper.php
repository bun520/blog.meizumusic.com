<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('create_signature')) {
	function create_signature(Array $params, $algo = 'md5', $secretKey = '', $paychannel = '')
	{
		$signPar = '';
		switch ($paychannel) {
			default:
				ksort($params);
				reset($params);
				foreach ($params as $key => $value) {
					if ('' === $value || $key == 'sign_type') {
						continue;
					}
					$signPar .= $key . '=' . $value . '&';
				}

				$signPar = substr($signPar, 0, strlen($signPar) - 1);
				break;
		}
		$sign = '';
		switch (strtolower($algo)) {
			case 'md5':
				$sign = md5($signPar . $secretKey);
				break;
			case 'sha1':
				$sign = sha1($signPar . $secretKey);
				break;
		}


		switch ($paychannel) {
			case 'weibo':
				return $sign;
				break;
			default:
				return strtoupper($sign);
				break;
		}
	}
}


if (!function_exists('verify_signature')) {
	function verify_signature(Array $params, $algo = 'md5', $secretKey = '', $paychannel = '')
	{
		if (!array_key_exists('sign', $params)) {
			log_message('error', __FUNCTION__ . ': sign not in weixin return data');

			return false;
		}
		$sign = $params['sign'];
		unset($params['sign']);

		$verify_sign = create_signature($params, $algo, $secretKey, $paychannel);

		return (boolean)($verify_sign === $sign);
	}
}

