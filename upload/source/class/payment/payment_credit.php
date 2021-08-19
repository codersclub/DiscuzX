<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: payment_credit.php 36342 2021-05-17 15:12:53Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class payment_credit {

	public function callback($data, $order) {

		global $_G;
		C::t('forum_order')->insert(array(
			'orderid' => $order['out_biz_no'],
			'status' => '2',
			'buyer' => $order['id'],
			'admin' => 0,
			'uid' => $order['uid'],
			'amount' => $data['value'],
			'price' => $order['amount'] / 100,
			'submitdate' => $_G['timestamp'],
			'email' => $_G['member']['email'],
			'confirmdate' => $order['payment_time'],
			'ip' => $data['ip'],
			'port' => $data['port']
		));
		updatemembercount($order['uid'], array('extcredits' . $data['index'] => $data['value']), 1, 'AFD', $order['uid']);
		C::t('forum_order')->delete_by_submitdate($_G['timestamp'] - 60 * 86400);

		$extcredits = $_G['setting']['extcredits'][$data['index']];
		notification_add($order['uid'], 'credit', 'addfunds', array('orderid' => $order['out_biz_no'], 'price' => $order['amount'] / 100, 'value' => trim($extcredits['title'] . ' ' . $data['value'] . ' ' . $extcredits['unit'])), 1);
	}

}

?>