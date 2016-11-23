<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('compare_array_int')) {
    function compare_array_int($key) {
        return function ($a, $b) use ($key) {
            $var_a = intval($a[$key]);
            $var_b = intval($b[$key]);
            if ($var_a == $var_b) {
                return 0;
            }

            return $var_a > $var_b ? 1 : -1;
        };
    }
}

if (!function_exists('compare_array_str')) {
    function compare_array_str($key, $orderBy = 'ASC') {
        return function ($a, $b) use ($key, $orderBy) {
            $var_a = strval($a[$key]);
            $var_b = strval($b[$key]);

            if ($var_a == $var_b) {
                return 0;
            }
            if (strtoupper($orderBy) == 'DESC') {
                return $var_a < $var_b ? 1 : -1;
            }

            return $var_a > $var_b ? 1 : -1;

        };
    }
}
