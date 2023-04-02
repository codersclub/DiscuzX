<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_main.php 36284 2016-12-12 00:47:50Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

lang('admincp_menu');

$extra = cpurl('url');
$extra = $extra && getgpc('action') ? $extra : 'action=index';
$charset = CHARSET;
$title = cplang('admincp_title');
$header_welcome = cplang('header_welcome');
$header_logout = cplang('header_logout');
$header_bbs = cplang('header_bbs');
if(isfounder()) {
	cplang('founder_admin');
} else {
	if($GLOBALS['admincp']->adminsession['cpgroupid']) {
		$cpgroup = C::t('common_admincp_group')->fetch($GLOBALS['admincp']->adminsession['cpgroupid']);
		$cpadmingroup = $cpgroup['cpgroupname'];
	} else {
		cplang('founder_master');
	}
}
require './source/admincp/admincp_menu.php';
$basescript = ADMINSCRIPT;
$staticurl = STATICURL;
$oldlayout = empty($_G['cookie']['admincp_leftlayout']) ? ' class="oldlayout"' : '';

echo <<<EOT
<!DOCTYPE html>
<html><head>
<meta charset="$charset">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light dark">
<title>$title</title>
<meta content="Comsenz Inc." name="Copyright" />
<link rel="stylesheet" href="{$staticurl}image/admincp/minireset.css?{$_G['style']['verhash']}" type="text/css" media="all" />
<link rel="stylesheet" href="{$staticurl}image/admincp/admincpframe.css?{$_G['style']['verhash']}" type="text/css" media="all" />
<script src="{$_G['setting']['jspath']}common.js?{$_G['style']['verhash']}" type="text/javascript"></script>
</head>
<body>
<div id="append_parent"></div>
$shownotice
<div id="bdcontainer"$oldlayout>
	<div id="navcontainer" class="navcontainer">
		<nav>
			<a href="$basescript?frames=yes&action=index" class="logo">
				<img src="{$staticurl}image/admincp/logo.svg" alt="Discuz! Administrator's Control Panel">
			</a>
			<ul id="leftmenu">
EOT;

$uc_api_url = $uchtml = '';
if($isfounder) {
	loaducenter();
	if(!UC_STANDALONE) {
		$uc_api_url = UC_API;
		$uchtml = '<li><em><a id="header_uc" hidefocus="true" href="'.UC_API.'/admin.php?m=frame" onmouseover="previewheader(\'uc\')" onmouseout="previewheader()" onclick="uc_login=1;toggleMenu(\'uc\', \'\');doane(event);">'.cplang('header_uc').'</a></em></li>';
		$topmenu['uc'] = '';
		$menu['uc'] = array(array('header_uc', UC_API.'/admin.php?m=frame', '_blank'));
	}
}

foreach($topmenu as $k => $v) {
	if($k == 'cloud') {
		continue;
	}
	echo '<li id="lm_'.$k.'">';
	showleftheader($k);
	showmenu($k, $menu[$k]);
	echo '</li>';
}

echo <<<EOT
			</ul>
		</nav>
	</div>
	<div class="ifmcontainer">
		<div class="mainhd">
			<div id="navbtn"><div></div></div>
			<div class="currentloca" id="admincpnav"></div>
			<form name="search" method="post" autocomplete="off" action="$basescript?action=search" target="main">
				<input type="text" name="keywords" value="" class="txt" required>
				<button type="submit" name="searchsubmit" value="yes" class="btn"></button>
			</form>
			<div class="uinfo">
				<ul id="topmenu">
EOT;

foreach($topmenu as $k => $v) {
	if($k == 'cloud') {
		continue;
	}
	if($v === '') {
		$v = is_array($menu[$k]) ? array_keys($menu[$k]) : array();
		$v = $menu[$k][$v[0]][1];
	}
	showheader($k, $v);
}
unset($menu);

$headers = "'".implode("','", array_keys($topmenu))."'";
$useravt = avatar(getglobal('uid'), 'middle', array('class' => 'avt'));

echo <<<EOT
			</ul>
				<div id="frameuinfo">
					{$useravt}
					<p class="greet">$header_welcome, $cpadmingroup <em>{$_G['member']['username']}</em> <a href="$basescript?action=logout" target="_top">$header_logout</a></p>
					<p class="btnlink"><a href="index.php" target="_blank" title="$header_bbs"><svg width="24" height="24">
						<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
					</svg></a></p>
					<p class="btnlink" id="cpsetting"><svg width="24" height="24">
						<path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
					</svg></p>
					<p class="btnlink"><div class="darkmode" title="$light_mode">
					<div>
					<div class="dk-light">
					<svg width="24" height="24">
					<path d="M18 12c0 3.3-2.7 6-6 6s-6-2.7-6-6 2.7-6 6-6 6 2.7 6 6zm-6-7c.3 0 .5-.2.5-.5v-4c0-.3-.2-.5-.5-.5s-.5.2-.5.5v4c0 .3.2.5.5.5zm0 14c-.3 0-.5.2-.5.5v4c0 .3.2.5.5.5s.5-.2.5-.5v-4c0-.3-.2-.5-.5-.5zm-7-7c0-.3-.2-.5-.5-.5h-4c-.3 0-.5.2-.5.5s.2.5.5.5h4c.3 0 .5-.2.5-.5zm18.5-.5h-4c-.3 0-.5.2-.5.5s.2.5.5.5h4c.3 0 .5-.2.5-.5s-.2-.5-.5-.5zM6.3 7c.2.2.5.2.7 0 .2-.2.2-.5 0-.7L4.2 3.5c-.2-.2-.5-.2-.7 0s-.2.5 0 .7L6.3 7zm11.4 9.9c-.2-.2-.5-.2-.7 0-.2.2-.2.5 0 .7l2.8 2.8c.2.2.5.2.7 0s.2-.5 0-.7l-2.8-2.8zm0-9.9l2.8-2.8c.2-.2.2-.5 0-.7s-.5-.2-.7 0L17 6.3c-.2.2-.2.5 0 .7.1.2.5.2.7 0zM6.3 16.9l-2.8 2.8c-.2.2-.2.5 0 .7s.5.2.7 0L7 17.6c.2-.2.2-.5 0-.7-.1-.2-.5-.2-.7 0z" />
					</svg>
					</div>
					<div class="dk-dark">
					<svg width="24" height="24">
					<path d="M13.1 22c3.1 0 5.9-1.4 7.8-3.7.3-.3 0-.8-.5-.8-4.9.9-9.3-2.8-9.3-7.7 0-2.8 1.5-5.4 4-6.8.4-.2.3-.8-.1-.9-.7-.1-1.3-.1-1.9-.1-5.5 0-10 4.5-10 10s4.4 10 10 10z" />
					</svg>
					</div>
					</div>
					<ul id="dkm_menu" style="display: none;"><li class="current">$by_system</li><li>$normal_mode</li><li>$dark_mode</li></ul>
					</div></p>
				</div>
			</div>
		</div>
		<iframe src="$basescript?$extra" id="main" name="main"></iframe>
	</div>
</div>
<script>var cookiepre = '{$_G['config']['cookie']['cookiepre']}', cookiedomain = '{$_G['config']['cookie']['cookiedomain']}', cookiepath = '{$_G['config']['cookie']['cookiepath']}';
var headers = new Array($headers), admincpfilename = '$basescript', admincpextra = '$extra';</script>
<script src="{$_G['setting']['jspath']}admincp_frame.js?{$_G['style']['verhash']}" type="text/javascript"></script>
<script>
	init_darkmode();
</script>
</body></html>
EOT;

?>