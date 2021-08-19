<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: notify_wechat.php 36342 2021-05-17 14:15:04Z dplugin $
 */

define('IN_API', true);
define('CURSCRIPT', 'api');
define('DISABLEXSSCHECK', true);

require '../../../source/class/class_core.php';
require '../payment_wechat.php';

$discuz = C::app();
$discuz->init();

$payment = new payment_wechat();
if($_SERVER['HTTP_WECHATPAY_SIGNATURE']) {
	$data = $payment->v3_wechat_sign_verify();
	if($data && $data['code'] == 200) {
		$data = json_decode($data['data'], true);
		if($data['trade_state'] == 'SUCCESS') {
			$out_biz_no = $data['out_trade_no'];
			$payment_time = strtotime($data['success_time']);
			$is_success = payment::finish_order('wechat', $out_biz_no, $data['transaction_id'], $payment_time);
			if($is_success) {
				exit('{"code":"SUCCESS","message":"ok"}');
			}
		}
	} else {
		payment::paymentlog('wechat', 0, 0, 0, 50001, $data ? json_encode($data) : '');
	}
	exit('{"code":"fail","message":"fail"}');
} else {
	$data = $payment->wechat_sign_verify();
	if($data && $data['code'] == 200) {
		$data = $data['data'];
		$out_biz_no = $data['out_trade_no'];
		$payment_time = strtotime(preg_replace('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $data['time_end']));
		$is_success = payment::finish_order('wechat', $out_biz_no, $data['transaction_id'], $payment_time);
		if($is_success) {
			echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
			exit();
		}
	} else {
		payment::paymentlog('wechat', 0, 0, 0, 50001, $data ? json_encode($data) : '');
	}
	echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
	exit();
}

?>