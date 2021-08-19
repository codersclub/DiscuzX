<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_payment.php 36342 2021-05-17 15:26:19Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$operation = in_array($_GET['op'], array('order', 'pay')) ? trim($_GET['op']) : 'order';
$opactives = array($operation => ' class="a"');

if($_G['setting']['ec_ratio']) {
	$is_enable_pay = payment::enable();
} else {
	$is_enable_pay = false;
}

if(!$_G['setting']['ec_ratio'] || !$is_enable_pay) {
	showmessage('action_closed', null);
}

include_once libfile('spacecp/payment_' . $operation, 'include');


?>