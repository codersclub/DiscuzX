<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 */

class filesock {
	public static function open($param = array()) {
		$allowcurl = true;
		if(isset($param['allowcurl']) && $param['allowcurl'] == false) {
			$allowcurl = false;
		}
		if(function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
			return new filesock_curl($param);
		} else {
			return new filesock_stream($param);
		}
	}
}
