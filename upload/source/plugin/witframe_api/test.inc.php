<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/*
// 调用范例

// 引用库
require_once DISCUZ_ROOT.'./source/plugin/witframe_api/core.php';

$siteuniqueid = $_G['setting']['siteuniqueid'];

// 自动注册站点信息，获取站点配置信息
$conf = Lib\Site::Discuz_GetConf($siteuniqueid);
print_r($conf);

// 添加应用的授权，自动添加 Sample 应用的授权
Lib\Site::AddAuthInfo(10005, 1003, 'abcdE');

// 调用apis
$r = Lib\Apis::Sample_v1_apis(['now' => time()]);
print_r($r);

// 返回登录Wit的链接
$r = Lib\Site::Discuz_LoginWit($siteuniqueid);
echo $r['url'];

*/