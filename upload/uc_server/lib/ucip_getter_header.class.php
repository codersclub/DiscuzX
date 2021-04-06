<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ucip_getter_header.class.php 809 2019-12-19 12:00:00Z community $
*/

class ucip_getter_header {

	public static function get($s) {
		if (empty($s['header'])) {
			return $_SERVER['REMOTE_ADDR'];
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		if ($s['header'] != 'HTTP_X_FORWARDED_FOR') {
			$ip = ucip::validate_ip($_SERVER[$s['header']]) ? $_SERVER[$s['header']] : $ip;
		} else {
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") > 0) {
				$exp = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
				$ip = ucip::validate_ip(trim($exp[0])) ? $exp[0] : $ip;
			} else {
				$ip = ucip::validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
			}
		}
		return $ip;
	}

}