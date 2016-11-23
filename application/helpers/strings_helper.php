<?php

if (!function_exists('u_str_replace')) {
    function u_str_replace($search, $replace, $subject) {
        if (!is_string($subject)) {
            return $subject;
        }

        return str_replace($search, $replace, $subject);
    }
}

if (!function_exists('strip_crlf')) {
    function strip_crlf($subject) {
        $search  = array("\r\n", "\n", "\r");
        $replace = '';

        return str_replace($search, $replace, $subject);
    }
}
