<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ucip.class.php 803 2019-12-19 12:00:00Z community $
*/

class ucip {

	function __construct() {
	}

	/*
	 * 验证IP是否合法，支持v4和v6
	 */
	public static function validate_ip($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
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

}
?>