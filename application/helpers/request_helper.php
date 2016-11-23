<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('create_request')) {
    function create_request($host, $api, $params, $method = 'GET', $scheme = 'http') {
        $request = array(
            'host'   => $host,
            'api'    => $api,
            'method' => $method,
            'params' => $params,
            'scheme' => $scheme,
        );

        return $request;
    }
}

if (!function_exists('do_request')) {
    function do_mcurl_request($mcurl, $requests) {
        try {
            $result = $mcurl->capture_multi($requests, false);
            if (isset($result[0])) {
                return $result[0];
            }

            return (object) array();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . json_encode($requests), $e->getCode());
        }
    }
}

if (!function_exists('do_curl_request')) {
    function do_curl_request($url, $hostName, $params, $method, $ms = false, $opts = array()) {
        try {
            $postString = '';
            if (is_array($params)) {
                $postParams = array();
                if (!empty($params)) {
                    foreach ($params as $key => $val) {
                        $post_params[] = $key . '=' . urlencode($val);
                    }

                    $postString = implode('&', $post_params);
                }
            } else {
                $postString = $params;
            }

            $result = '';
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                if (!empty($hostName)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $hostName));
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Version:' . phpversion());
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                if (true === $ms) {
                    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
                }
                if (!empty($opts)) {
                    foreach ($opts as $key => $value) {
                        curl_setopt($ch, $key, $value);
                    }
                }
                $result      = curl_exec($ch);
                $httpcode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                return $result;
            }

            return false;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
