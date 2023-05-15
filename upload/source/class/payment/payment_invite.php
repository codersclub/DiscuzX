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

class payment_invite {

	public function callback($data, $order) {
		global $_G;
		C::t('forum_order')->insert(array(
			'orderid' => $order['out_biz_no'],
			'status' => '2',
			'buyer' => $order['id'],
			'uid' => 0,
			'admin' => 0,
			'amount' => $data['num'],
			'price' => $order['amount'] / 100,
			'submitdate' => $_G['timestamp'],
			'email' => $data['email'],
			'confirmdate' => $order['payment_time'],
			'ip' => $data['ip'],
			'port' => $data['port'],
		));

		$codes = $codetext = array();
		$dateline = TIMESTAMP;
		for($i=0; $i<$data['num']; $i++) {
			$code = strtolower(random(6));
			$codetext[] = $code;
			$codes[] = "('0', '$code', '$dateline', '".($_G['group']['maxinviteday']?($_G['timestamp']+$_G['group']['maxinviteday']*24*3600):$_G['timestamp']+86400*10)."', '{$data['email']}', '{$data['ip']}', '{$order['out_biz_no']}')";
			$invitedata = array(
				'uid' => 0,
				'code' => $code,
				'dateline' => $dateline,
				'endtime' => $_G['group']['maxinviteday'] ? ($_G['timestamp']+$_G['group']['maxinviteday']*24*3600) : $_G['timestamp']+86400*10,
				'email' => $data['email'],
				'inviteip' => $data['ip'],
				'orderid' => $order['out_biz_no']
			);
			C::t('common_invite')->insert($invitedata);
		}

		if(!function_exists('sendmail')) {
			include libfile('function/mail');
		}
		$add_member_subject = $_G['setting']['bbname'].' - '.lang('forum/misc', 'invite_payment');
		$add_member_message = lang('email', 'invite_payment_email_message', array(
			'orderid' => $order['out_biz_no'],
			'codetext' => implode('<br />', $codetext),
			'siteurl' => $_G['siteurl'],
			'bbname' => $_G['setting']['bbname'],
		));
		if(!sendmail($data['email'], $add_member_subject, $add_member_message)) {
			runlog('sendmail', "{$data['email']} sendmail failed.");
		}
	}

}

?>