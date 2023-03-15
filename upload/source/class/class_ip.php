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
			$newmask = intval($mask);
			$newip = self::to_ip($newip);
			if (!self::validate_ip($newip)) {
				return FALSE;
			}
			if($newmask > 128 || ($newmask > 32 && strpos($newip, ':') === FALSE)) {
				return FALSE;
			}
			$new_str = $newip . "/" . $mask;
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * 给一个ipv4或v6的cidr，计算最小IP和最大IP
	 * 如果输入的是一个IP，那最大最小IP都等于其自身
	 * $as_hex = true
	 * 	返回值为 二进制表达的字符串格式
	 * $as_hex = false
	 * 	返回值可用inet_ntop轮换为IP字符串表达式 
	 */
	public static function calc_cidr_range($str, $as_hex = false) {
		if(self::validate_cidr($str, $str)) {
			list($ip, $prefix) = explode('/', $str);
		} elseif (filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$ip = $str;
			$prefix = 32;
		} elseif (filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$ip = $str;
			$prefix = 128;
		} else {
			return FALSE;
		}

		$ip_bytes = unpack("C*", inet_pton($ip));
		$total_bytes = count($ip_bytes);
		$num_diff_bits = 8 * $total_bytes - $prefix;
		if ($num_diff_bits >= 0) {
			$num_same_bytes = $prefix >> 3;
			$same_bytes = array_slice($ip_bytes, 0, $num_same_bytes);
			$diff_bytes_start = ($total_bytes === $num_same_bytes) ? array() : array_fill(0, $total_bytes - $num_same_bytes, 0);
			$diff_bytes_end = ($total_bytes === $num_same_bytes) ? array() : array_fill(0, $total_bytes - $num_same_bytes, 255);
			$start_same_bits = $prefix % 8;
			if ($start_same_bits !== 0) {
				$vary_byte = $ip_bytes[$num_same_bytes];
				$diff_bytes_start[0] = $vary_byte & bindec(str_pad(str_repeat('1', $start_same_bits), 8, '0', STR_PAD_RIGHT));
				$diff_bytes_end[0] = $diff_bytes_start[0] + bindec(str_repeat('1', 8 - $start_same_bits));
			} 
			
			$start_array = array_merge($same_bytes, $diff_bytes_start);
			$end_array = array_merge($same_bytes, $diff_bytes_end);
			if ($as_hex) {
				if ($total_bytes < 16) {
					$start_array = array_merge(array_fill(0, 16 - $total_bytes, 0), $start_array);
					$end_array = array_merge(array_fill(0, 16 - $total_bytes, 0), $end_array);
				}
				$start = unpack('H*hex', join(array_map('chr', $start_array)))['hex'];
				$end = unpack('H*hex', join(array_map('chr', $end_array)))['hex'];
				return array($start, $end);
			} else {
				$start = call_user_func_array('pack', array_merge(array("C*"), $start_array));
				$end = call_user_func_array('pack', array_merge(array("C*"), $end_array));
				return array($start, $end);
			}
		}

		return FALSE;
	}

	/*
	 * 将一个IP地址转为16进制表达的字符串
	 */
	public static function ip_to_hex_str($ip)
	{
		if (!self::validate_ip($ip)) {
			return false;
		}
		$ip_bytes = unpack("C*", inet_pton($ip));
		$total_bytes = count($ip_bytes);
		if ($total_bytes < 16) {
			$ip_bytes = array_merge(array_fill(0, 16 - $total_bytes, 0), $ip_bytes);
		}
		return unpack('H*hex', join(array_map('chr', $ip_bytes)))['hex'];
	}

	/*
	 * 以下三个函数，检查$requestIp是否在$ip给出的cidr范围内
	 */

	public static function check_ip($requestIp, $ips)
	{
		if (!self::validate_ip($requestIp)) {
			return false;
		}
		if (!\is_array($ips)) {
			$ips = [$ips];
		}
		$method = substr_count($requestIp, ':') > 1 ? 'check_ip6' : 'check_ip4';
		foreach ($ips as $ip) {
			if (self::$method($requestIp, $ip)) {
				return true;
			}
		}
		return false;
	}

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

	public static function check_ip4($requestIp, $ip)
	{
		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);
			if ('0' === $netmask) {
				return false;
			}
			if ($netmask < 0 || $netmask > 32) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 32;
		}
		if (false === ip2long($address)) {
			return false;
		}
		return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
	}

	/*
	 * 将IP转为位置，支持传入CIDR
	 */
	public static function convert($ip) {
		global $_G;
		if (false !== strpos($ip, '/')) {
			list($ip, $netmask) = explode('/', $ip, 2);
		}
		if(!self::validate_ip($ip)) {
			return '- Invalid';
		}
		if (!(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false)) {
			return '- LAN';
		}
		if (!(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false)) {
			return '- Reserved';
		}
		if (array_key_exists('ipdb', $_G['config']) && array_key_exists('setting', $_G['config']['ipdb'])) {
			$s = $_G['config']['ipdb']['setting'];
			if (!empty($s['fullstack'])) {
				$c = 'ip_'.$s['fullstack'];
			} else if (!empty($s['ipv4']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
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
		$ipobject = $c::getInstance();
		return $ipobject === NULL ? '- Error' : $ipobject->convert($ip);
	}

	public static function checkaccess($ip, $accesslist) {
		return preg_match("/^(".str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')).")/", $ip);
	}

	public static function checkbanned($ip) {
		global $_G;

		if (array_key_exists('security', $_G['config']) && array_key_exists('useipban', $_G['config']['security']) && $_G['config']['security']['useipban'] == 0) {
			return false;
		}

		if($_G['setting']['ipaccess'] && self::checkaccess($ip, $_G['setting']['ipaccess'])) {
			return true;
		}

		return C::t('common_banned')->check_banned(TIMESTAMP, $ip);
	}

}
?>