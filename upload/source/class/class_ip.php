<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_ip.php 2717 2019-12-03 12:00:00Z opensource $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class ip {

	function __construct() {
	}

	private function _validate_ip($ip) {
		return function_exists('filter_var') ? filter_var($host, FILTER_VALIDATE_IP) !== false : preg_match('/^((2[0-4]|1\d|[1-9])?\d|25[0-5])(\.(?1)){3}\z/', $host) !== false;
	}

	public static function get() {
		global $_G;
		$ip = $_SERVER['REMOTE_ADDR'];
		if (!array_key_exists('security', $_G['config']) || !$_G['config']['security']['onlyremoteaddr']) {
			if (isset($_SERVER['HTTP_CLIENT_IP']) && self::_validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") > 0) {
					$exp = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
					$ip = self::_validate_ip(trim($exp[0])) ? $exp[0] : $ip;
				} else {
					$ip = self::_validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
				}
			}
		}
		return $ip;
	}

	public static function convert($ip) {
		if(!self::_validate_ip($ip)) {
			return '- Invalid';
		} else {
			if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
				return '- LAN';
			}
			if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
				return '- Reserved';
			}
			if (array_key_exists('ipdb', $_G['config']) && array_key_exists('setting', $_G['config']['ipdb']) {
				$setting = $_G['config']['ipdb']['setting'];
				if (!empty($setting['ipv4']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					$c = 'ip_'.$setting['ipv4'];
				} else if (!empty($setting['ipv6']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$c = 'ip_'.$setting['ipv6'];
				} else if (!empty($setting['default'])) {
					$c = 'ip_'.$setting['default'];
				} else {
					$c = 'ip_tiny';
				}
			} else {
				$c = 'ip_tiny';
			}
			return $c::convert($ip);
		}
	}

	public static function checkaccess($ip, $accesslist) {
		return preg_match("/^(".str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')).")/", $ip);
	}

	public static function checkbanned($ip) {
		global $_G;
		if($_G['setting']['ipaccess'] && $this->checkaccess($ip, $_G['setting']['ipaccess'])) {
			return true;
		}
		foreach(C::t('common_banned')->fetch_all_order_dateline() as $banned) {
			if (cidr::match($ip, $banned['ip'])) {
				return true;
			}
		}
		return false;
	}

}
?>