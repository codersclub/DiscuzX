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

$output = array('result' => null);

if($_GET['action'] == 'send') {

	$refererhost = parse_url($_SERVER['HTTP_REFERER']);
	$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

	if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
		exit('Access Denied');
	}

	$type = empty($_GET['type']) ? 0 : $_GET['type'];
	$secmobicc = empty($_GET['secmobicc']) ? $_G['member']['secmobicc'] : $_GET['secmobicc'];
	$secmobile = empty($_GET['secmobile']) ? $_G['member']['secmobile'] : $_GET['secmobile'];

	$secmobileseccode = random(8, 1);
	$result = sms::sendseccode($_G['uid'], $type, $secmobicc, $secmobile, $secmobileseccode);

	$output = array('result' => $result);

}

header("Content-Type: application/json");
echo helper_json::encode($output);

?>