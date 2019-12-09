<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_ip.php 2017 2019-12-03 12:00:00Z opensource $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class ip {

	function __construct() {
	}

	/*
	 * 将IPv6地址外面加方括号，用于显示
	 */
	public static function to_display($ip) {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return '[' . $ip . ']';
		}
		return $ip;
	}

	/*
	 * 将各种显示格式的IPv6地址处理回标准IPv6格式
	 * [::1] -> ::1
	 * [::1]/16 -> ::1/16
	 */
	public static function to_ip($ip) {
		if (strlen($ip) == 0) return $ip;
		if (preg_match('/(.*?)\[((.*?:)+.*)\](.*)/', $ip, $m)) { // [xx:xx:xx]格式
			if (filter_var($m[2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ip = $m[1].$m[2].$m[4];
			}
		}
		return $ip;
	}

	/*
	 * 验证IP是否合法，支持v4和v6
	 */
	public static function validate_ip($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}

	/*
	 * 验证是否是合法的CIDR:
	 * 	- 包含 /
	 * 	- / 后面大于0
	 * 	- / 前面是合法的IP
	 * 返回值：
	 * 	- TRUE，表示是合法的CIDR，$new_str为处理过的CIDR(IP部分调用了to_ip)
	 * 	- FALSE, 不是合法的CIDR
	 */
	public static function validate_cidr($str, &$new_str) {
		if(strpos($str, '/') !== false) {
			list($newip, $mask) = explode('/', $str);
			if($mask <= 0) {
				return FALSE;
			}
			$newip = self::to_ip($newip);
			if (!self::validate_ip($newip)) {
				return FALSE;
			}
			$new_str = $newip . "/" . $mask;
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * 检查$requestIp是否在$ip给出的cidr范围内
	 */
	public static function check_ip6($requestIp, $ip)
	{
		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);
			if ('0' === $netmask) {
				return (bool) unpack('n*', @inet_pton($address));
			}
			if ($netmask < 1 || $netmask > 128) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}
		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIp));
		if (!$bytesAddr || !$bytesTest) {
			return false;
		}
		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xffff >> $left) & 0xffff;
			if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
				return false;
			}
		}
		return true;
	}

	public static function convert($ip) {
		global $_G;
		if(!self::validate_ip($ip)) {
			return '- Invalid';
		} else {
			if (!(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false)) {
				return '- LAN';
			}
			if (!(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false)) {
				return '- Reserved';
			}
			if (array_key_exists('ipdb', $_G['config']) && array_key_exists('setting', $_G['config']['ipdb'])) {
				$s = $_G['config']['ipdb']['setting'];
				if (!empty($s['ipv4']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
					$c = 'ip_'.$s['ipv4'];
				} else if (!empty($s['ipv6']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
					$c = 'ip_'.$s['ipv6'];
				} else if (!empty($s['default'])) {
					$c = 'ip_'.$s['default'];
				} else {
					$c = 'ip_tiny';
				}
			} else {
				$c = 'ip_tiny';
			}
			return $c::getInstance()->convert($ip);
		}
	}

	public static function checkaccess($ip, $accesslist) {
		return preg_match("/^(".str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')).")/", $ip);
	}

	public static function checkbanned($ip) {
		global $_G;
		if($_G['setting']['ipaccess'] && self::checkaccess($ip, $_G['setting']['ipaccess'])) {
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