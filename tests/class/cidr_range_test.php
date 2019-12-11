<?php

define('IN_DISCUZ', true);
require_once '../upload/source/class/class_ip.php';

class TestCidrRange
{
	/*
	 * 使用 https://www.ipaddressguide.com/cidr 生成结果对比
	 */
	function testV4() {
		list($start, $end) = ip::calc_cidr_range("10.0.0.0/24");
		assertEqual("10.0.0.0", inet_ntop($start));
		assertEqual("10.0.0.255", inet_ntop($end));

		list($start, $end) = ip::calc_cidr_range("0.0.0.0/24");
		assertEqual("0.0.0.0", inet_ntop($start));
		assertEqual("0.0.0.255", inet_ntop($end));

		list($start, $end) = ip::calc_cidr_range("172.16.3.8/17");
		assertEqual("172.16.0.0", inet_ntop($start));
		assertEqual("172.16.127.255", inet_ntop($end));
	}

	/*
	 * 使用 https://www.ultratools.com/tools/ipv6CIDRToRangeResult 生成结果对比
	 */
	function testV6() {
		list($start, $end) = ip::calc_cidr_range("::1/64");
		assertEqual("::", inet_ntop($start));
		assertEqual("::ffff:ffff:ffff:ffff", inet_ntop($end));

		list($start, $end) = ip::calc_cidr_range("fc00:2000:1000::1/34");
		assertEqual("fc00:2000::", inet_ntop($start));
		assertEqual("fc00:2000:3fff:ffff:ffff:ffff:ffff:ffff", inet_ntop($end));
	}
}

?>