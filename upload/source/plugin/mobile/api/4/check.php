<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: check.php 36332 2016-12-30 01:44:19Z nemohou $
 */

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

require './source/class/class_core.php';

$discuz = C::app();
$discuz->init();

if(!defined('DISCUZ_VERSION')) {
	require './source/discuz_version.php';
}

if(in_array('mobile', $_G['setting']['plugins']['available'])) {
	loadcache('wsq_checkinfo');
	if (!$_G['cache']['wsq_checkinfo'] || TIMESTAMP - $_G['cache']['wsq_checkinfo']['expiration'] > 600) {
		$_G['wechat']['setting'] = dunserialize($_G['setting']['mobilewechat']);
		$forums = C::t('forum_forum')->fetch_all_by_status(1);
		foreach ($forums as $forum) {
			$posts += $forum['posts'];
		}
		loadcache('userstats');
		// 由于微社区依赖 discuzversion == X3.2, 且微社区平台代码无法修改
		// 因此在 X3.5 使用 truediscuzversion 标示正确的 Discuz! Version.
		$array = array(
			'discuzversion' => 'X3.2',
			'truediscuzversion' => DISCUZ_VERSION,
			'charset' => CHARSET,
			'version' => MOBILE_PLUGIN_VERSION,
			'pluginversion' => $_G['setting']['plugins']['version']['mobile'],
			'regname' => $_G['setting']['regname'],
			'qqconnect' => in_array('qqconnect', $_G['setting']['plugins']['available']) ? '1' : '0',
			'wsqqqconnect' => in_array('qqconnect', $_G['setting']['plugins']['available']) ? '1' : '0',
			'wsqhideregister' => $_G['wechat']['setting']['wechat_allowregister'] && $_G['wechat']['setting']['wechat_allowfastregister'] ? '1' : '0',
			'sitename' => $_G['setting']['bbname'],
			'mysiteid' => $_G['setting']['my_siteid'],
			'ucenterurl' => $_G['setting']['ucenterurl'],
			'defaultfid' => $_G['wechat']['setting']['wsq_fid'],
			'totalposts' => $posts,
			'totalmembers' => $_G['cache']['userstats']['totalmembers'],
		);
		savecache('wsq_checkinfo', array('variable' => $array, 'expiration' => TIMESTAMP));
	} else {
		$array = $_G['cache']['wsq_checkinfo']['variable'];
	}
} else {
    $array = array();
}

$array['testcookie'] = $_G['cookie']['testcookie'];
$data = mobile_core::json($array);
mobile_core::make_cors($_SERVER['REQUEST_METHOD'], REQUEST_METHOD_DOMAIN);

echo $data;

?>