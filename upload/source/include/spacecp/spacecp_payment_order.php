<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_payment_order.php 36342 2021-05-17 15:26:53Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
if($page < 1) {
	$page = 1;
}
$perpage = 20;

$start = ($page - 1) * $perpage;

$gets = array(
	'mod' => 'spacecp',
	'ac' => 'payment',
	'starttime' => $_GET['starttime'],
	'endtime' => $_GET['endtime'],
	'optype' => $_GET['optype']
);
$theurl = 'home.php?' . url_implode($gets);
$multi = '';

$endunixstr = $beginunixstr = 0;
if($_GET['starttime']) {
	$beginunixstr = strtotime($_GET['starttime']);
	$_GET['starttime'] = dgmdate($beginunixstr, 'Y-m-d');
}
if($_GET['endtime']) {
	$endunixstr = strtotime($_GET['endtime'] . ' 23:59:59');
	$_GET['endtime'] = dgmdate($endunixstr, 'Y-m-d');
}
if($beginunixstr && $endunixstr && $endunixstr < $beginunixstr) {
	showmessage('start_time_is_greater_than_end_time');
}

$payment_type_data = C::t('common_payment_order')->fetch_type_all($_G['uid']);

$optype = '';
if($_GET['optype'] && in_array($_GET['optype'], array_keys($payment_type_data))) {
	$optype = $_GET['optype'];
}

$count = C::t('common_payment_order')->count_by_search($_G['uid'], $optype, $beginunixstr, $endunixstr);
$order_list = array();
if($count) {
	foreach(C::t('common_payment_order')->fetch_all_by_search($_G['uid'], $optype, $_GET['starttime'], $_GET['endtime'], '', '', '', $start, $perpage) as $order) {
		$order['type_name'] = dhtmlspecialchars($order['type_name']);
		$order['amount'] = number_format($order['amount'] / 100, 2, '.', ',');
		$order['subject'] = dhtmlspecialchars($order['subject']);
		$order['description'] = dhtmlspecialchars($order['description']);
		$order['dateline'] = dgmdate($order['dateline'], 'Y-m-d H:i:s');
		$status = $order['status'];
		if(!$order['status'] && $order['expire_time'] < time()) {
			$status = 2;
		}
		$order['status'] = $status;
		$order['status_name'] = lang('spacecp', 'payment_status_' . $status);
		$order_list[] = $order;
	}
}

if($count) {
	$multi = multi($count, $perpage, $page, $theurl);
}

$optypehtml = '<select id="optype" name="optype" class="ps" width="168">';
$optypehtml .= '<option value="">' . lang('spacecp', 'logs_select_operation') . '</option>';
foreach($payment_type_data as $type => $title) {
	$optypehtml .= '<option value="' . $type . '"' . ($type == $_GET['optype'] ? ' selected="selected"' : '') . '>' . $title . '</option>';
}
$optypehtml .= '</select>';

include template('home/spacecp_payment_order');

?>