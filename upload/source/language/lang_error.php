<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: lang_error.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$lang = array
(
	'System Message' => '站点信息',

	'config_notfound' => '配置文件 "config_global.php" 未找到或者无法访问， 请确认您已经正确安装了程序',
	'template_notfound' => '模版文件未找到或者无法访问',
	'directory_notfound' => '目录未找到或者无法访问',
	'request_tainting' => '您当前的访问请求当中含有非法字符，已经被系统拒绝',
	'db_help_link' => '点击这里寻求帮助',
	'db_error_message' => '错误信息',
	'db_error_sql' => '<b>SQL</b>: $sql<br />',
	'db_error_backtrace' => '<b>Backtrace</b>: $backtrace<br />',
	'db_error_no' => '错误代码',
	'db_notfound_config' => '配置文件 "config_global.php" 未找到或者无法访问。',
	'db_notconnect' => '无法连接到数据库服务器',
	'db_security_error' => '查询语句安全威胁',
	'db_query_sql' => '查询语句',
	'db_query_error' => '查询语句错误',
	'db_config_db_not_found' => '数据库配置错误，请仔细检查 config_global.php 文件',
	'system_init_ok' => '网站系统初始化完成，请<a href="index.php">点击这里</a>进入',
	'backtrace' => '运行信息',
	'error_end_message' => '<a href="http://{host}">{host}</a> 已经将此出错信息详细记录, 由此给您带来的访问不便我们深感歉意',
	'suggestion_user' => '如果您是用户，建议您尝试刷新页面、关闭所有浏览器窗口重新进行操作。如果无法解决，建议您完整截图本页面并保存，随后向站点管理员反馈此问题',
	'suggestion_plugin' => '如果您是站点管理员，建议您尝试在管理中心关闭 <a href="admin.php?action=plugins&frames=yes" class="guess" target="_blank">{guess}</a> 插件并 <a href="admin.php?action=tools&operation=updatecache&frames=yes" class="guess" target="_blank">更新缓存</a> 。如关闭插件后问题解决，建议您凭完整截图联系插件供应方获得帮助',
	'suggestion' => '如果您是站点管理员，建议您尝试在管理中心 <a href="admin.php?action=tools&operation=updatecache&frames=yes" target="_blank">更新缓存</a> ，或凭完整截图通过 <a href="https://www.dismall.com/" target="_blank">官方论坛</a> 寻求帮助。如果您确定是程序自身Bug，您也可直接 <a href="https://gitee.com/discuz/DiscuzX/issues" target="_blank">提交Issue</a> 给我们',

	'file_upload_error_-101' => '上传失败！上传文件不存在或不合法，请返回。',
	'file_upload_error_-102' => '上传失败！非图片类型文件，请返回。',
	'file_upload_error_-103' => '上传失败！无法写入文件或写入失败，请返回。',
	'file_upload_error_-104' => '上传失败！无法识别的图像文件格式，请返回。',
);

?>