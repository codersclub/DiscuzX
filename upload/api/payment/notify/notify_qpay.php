<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id$
 */

define('IN_API', true);
define('CURSCRIPT', 'api');
define('DISABLEXSSCHECK', true);

require '../../../source/class/class_core.php';
require '../payment_qpay.php';

$discuz = C::app();
$discuz->init();

$payment = new payment_qpay();

$data = $payment->qpay_sign_verify();
if($data && $data['code'] == 200) {
	$data = $data['data'];
	$out_biz_no = $data['out_trade_no'];
	$payment_time = strtotime(preg_replace('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $data['time_end']));
	$is_success = payment::finish_order('qpay', $out_biz_no, $data['transaction_id'], $payment_time);
	if($is_success) {
		echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		exit();
	}
} else {
	payment::paymentlog('qpay', 0, 0, 0, 50001, $data ? json_encode($data) : '');
}

echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
exit();