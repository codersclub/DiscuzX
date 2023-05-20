<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_login.php 36284 2016-12-12 00:47:50Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if($this->core->var['inajax']) {
	ajaxshowheader();
	ajaxshowfooter();
}
if($this->cpaccess == -2 || $this->cpaccess == -3) {
	html_login_header(false);
} else {
	html_login_header();
}

if($this->cpaccess == -5) {
	echo  '<div class="loginbox">'.lang('admincp_login', 'login_cp_guest').'</div>';

}elseif($this->cpaccess == -2 || $this->cpaccess == -3) {
	echo  '<div class="container loginbox"><span>'.lang('admincp_login', 'login_cp_noaccess').'</span>';

}elseif($this->cpaccess == -1) {
	$ltime = $this->sessionlife - (TIMESTAMP - $this->adminsession['dateline']);
	echo  '<div class="loginbox"><span>'.lang('admincp_login', 'login_cplock', array('ltime' => $ltime)).'</span></div>';

}elseif($this->cpaccess == -4) {
	$ltime = $this->sessionlife - (TIMESTAMP - $this->adminsession['dateline']);
	echo  '<div class="loginbox"><span>'.lang('admincp_login', 'login_user_lock').'</span></div>';

} else {
	html_login_form();
}

html_login_footer();

function html_login_header($form = true) {
	global $_G;
	$charset = CHARSET;
	$cptitle = lang('admincp_login', 'admincp_title');
	$title = lang('admincp_login', 'login_title');
	$tips = lang('admincp_login', 'login_tips');
	$staticurl = STATICURL;
	$light_mode = lang('admincp_login', 'login_dk_light_mode');
	$by_system = lang('admincp_login', 'login_dk_by_system');
	$normal_mode = lang('admincp_login', 'login_dk_normal_mode');
	$dark_mode = lang('admincp_login', 'login_dk_dark_mode');
	echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="$charset">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="color-scheme" content="light dark">
<title>$title</title>
<link rel="stylesheet" href="{$staticurl}image/admincp/minireset.css?{$_G['style']['verhash']}">
<link rel="stylesheet" href="{$staticurl}image/admincp/admincplogin.css?{$_G['style']['verhash']}">
<meta content="Comsenz Inc." name="Copyright">
<script src="{$staticurl}js/common.js"></script>
<script src="{$staticurl}js/admincp_base.js"></script>
</head>
<body>
<div class="darkmode" title="$light_mode">
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
</div>
EOT;

	if($form) {
		echo <<<EOT
<div class="container">
<div class="intro">
<h3>$cptitle</h3>
<p>$tips</p>
</div>
EOT;
	}
}

function html_login_footer($halt = true) {
	$version = getglobal('setting/version');
	$cookiepre = getglobal('config/cookie/cookiepre');
	$copy = lang('admincp_login', 'copyright');
	echo <<<EOT
</div>
<footer><span>Powered by <a href="https://www.discuz.vip/" target="_blank">Discuz!</a> $version </span><span>$copy</span></footer>
<script>
	var cookiepre = '{$cookiepre}';
	if(self.parent.frames.length != 0) {
		self.parent.location=document.location;
	}
	init_darkmode();
</script>
</body>
</html>
EOT;

	$halt && exit();
}

function html_login_form() {
	global $_G;
	$isguest = !getglobal('uid');
	$lang = lang('admincp_login');
	$sid = getglobal('sid');
	$formhash = getglobal('formhash');
	$_SERVER['QUERY_STRING'] = str_replace('&amp;', '&', dhtmlspecialchars($_SERVER['QUERY_STRING']));
	$extra = ADMINSCRIPT.'?'.(getgpc('action') && getgpc('frames') ? 'frames=yes&' : '').$_SERVER['QUERY_STRING'];
	$forcesecques = '<option value="0">'.($_G['config']['admincp']['forcesecques'] || $_G['group']['forcesecques'] ? $lang['forcesecques'] : $lang['security_question_0']).'</option>';
	echo <<<EOT
		<form method="post" autocomplete="off" name="login" id="loginform" action="$extra" class="loginbox">
		<input type="hidden" name="sid" value="$sid">
		<input type="hidden" name="frames" value="yes">
		<input type="hidden" name="formhash" value="$formhash">
EOT;
	if($isguest) {
		echo <<<EOT
		<img class="logo" src="static/image/common/logo.svg">
		<input type="text" name="admin_username" placeholder="{$lang['login_username']}" autofocus autocomplete="off">
EOT;
	} else {
		echo avatar(getglobal('uid'),'middle',array('class' => 'avt')).'<h1>'.getglobal('member/username').'</h1>';
	}
	echo '<input type="password" name="admin_password" placeholder="'.$lang['login_password'].'" autocomplete="off"'.($isguest ? '' : 'autofocus').'>';
	echo <<<EOT
		<p onclick="document.querySelectorAll('.loginqa').forEach(vf=>{vf.className=''});this.style.display='none';"><span></span>{$lang['security_question']}</p>
		<select id="questionid" name="admin_questionid" class="loginqa">
			$forcesecques
			<option value="1">{$lang['security_question_1']}</option>
			<option value="2">{$lang['security_question_2']}</option>
			<option value="3">{$lang['security_question_3']}</option>
			<option value="4">{$lang['security_question_4']}</option>
			<option value="5">{$lang['security_question_5']}</option>
			<option value="6">{$lang['security_question_6']}</option>
			<option value="7">{$lang['security_question_7']}</option>
		</select>
		<input type="text" name="admin_answer" class="loginqa" placeholder="{$lang['security_answer']}" autocomplete="off">
		<button type="submit">{$lang['submit']}</button>
EOT;
	if (!empty($_G['admincp_checkip_noaccess'])) {
		echo  '<br><span>'.lang('admincp_login', 'login_ip_noaccess').'</span>';
	}
	echo <<<EOT
		</form>
EOT;
}

?>