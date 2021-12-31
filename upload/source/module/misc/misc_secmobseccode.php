<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id$
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

header("Content-Type: application/json");
$output = array('result' => null);

if($_GET['action'] == 'send') {

	$refererhost = parse_url($_SERVER['HTTP_REFERER']);
	$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

	if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
		exit(helper_json::encode($output));
	}

	$svctype = empty($_GET['svctype']) ? 0 : $_GET['svctype'];
	$secmobicc = empty($_GET['secmobicc']) ? $_G['member']['secmobicc'] : $_GET['secmobicc'];
	$secmobile = empty($_GET['secmobile']) ? $_G['member']['secmobile'] : $_GET['secmobile'];

	$length = $_G['setting']['smsdefaultlength'] ? $_G['setting']['smsdefaultlength'] : 4;
	$secmobseccode = random($length, 1);

	// 用户 UID : $_G['uid'], 短信类型: 验证类短信, 服务类型: $svctype
	// 国家代码: $secmobicc, 手机号: $secmobile, 内容: $secmobseccode, 强制发送: false
	$result = sms::send($_G['uid'], 0, $svctype, $secmobicc, $secmobile, $secmobseccode, 0);

	$output = array('result' => $result);

}

echo helper_json::encode($output);