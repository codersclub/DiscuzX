<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_buyinvitecode.php 31572 2012-09-10 08:59:03Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(submitcheck('buysubmit')) {
	if(payment::enable()) {
		$language = lang('forum/misc');
		$amount = intval($_GET['amount']);
		$email = dhtmlspecialchars($_GET['email']);
		if($amount < 1) {
			showmessage('buyinvitecode_no_count');
		}
		if(strlen($email) < 6 || !preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email)) {
			showmessage('buyinvitecode_email_error');
		}

		$price = $amount * $_G['setting']['inviteconfig']['invitecodeprice'] * 100;
		$orderid = '';

		$requesturl = payment::create_order(
			'payment_invite',
			$_G['setting']['bbname'].' - '.lang('forum/misc', 'invite_payment'),
			lang('forum/misc', 'invite_forum_payment').' '.intval($amount).' '.lang('forum/misc', 'invite_forum_payment_unit'),
			$price,
			$_G['siteurl'] . 'misc.php?mod=buyinvitecode&action=paysucceed&orderid={out_biz_no}',
			array(
				'num' => $amount,
				'email' => $email,
				'ip' => $_G['clientip'],
				'port' => $_G['remoteport']
			)
		);

		include isset($_REQUEST['inajax']) ? template('common/header_ajax') : template('common/header');
		echo '<form id="payform" action="'.$requesturl.'" method="post"></form><script type="text/javascript" reload="1">document.getElementById(\'payform\').submit();</script>';
		include isset($_REQUEST['inajax']) ? template('common/footer_ajax') : template('common/footer');
		dexit();
	} else {
		showmessage('action_closed', NULL);
	}

}
if($_GET['action'] == 'paysucceed' && $_GET['orderid']) {
	$orderid = $_GET['orderid'];
	$order = C::t('forum_order')->fetch($orderid);
	if(!$order) {
		showmessage('parameters_error');
	}
	$codes = array();
	foreach(C::t('common_invite')->fetch_all_orderid($orderid) as $code) {
		$codes[] = $code['code'];
	}
	if(empty($codes)) {
		showmessage('buyinvitecode_no_id');
	}
	$codetext = implode("\r\n", $codes);
}

if($_G['group']['maxinviteday']) {
	$maxinviteday = time() + 86400 * $_G['group']['maxinviteday'];
} else {
	$maxinviteday = time() + 86400 * 10;
}
$maxinviteday = dgmdate($maxinviteday, 'Y-m-d H:i');
$_G['setting']['inviteconfig']['invitecodeprompt'] = nl2br($_G['setting']['inviteconfig']['invitecodeprompt']);

include template('common/buyinvitecode');
?>