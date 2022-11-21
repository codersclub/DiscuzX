<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_index.php 36306 2016-12-16 08:12:49Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$sensitivedirs = array('./', './uc_server/', './ucenter/');

foreach ($sensitivedirs as $sdir) {
	if(@file_exists(DISCUZ_ROOT.$sdir.'install/index.php') && !DISCUZ_DEBUG) {
		@unlink(DISCUZ_ROOT.$sdir.'install/index.php');
		if(@file_exists(DISCUZ_ROOT.$sdir.'install/index.php')) {
			dexit('Please delete '.$sdir.'install/index.php via FTP!');
		}
	}
}

@include_once DISCUZ_ROOT.'./source/discuz_version.php';
require_once libfile('function/attachment');
require_once libfile('function/discuzcode');

if(submitcheck('notesubmit', 1)) {
	if(!empty($_GET['noteid']) && is_numeric($_GET['noteid'])) {
		C::t('common_adminnote')->delete_note($_GET['noteid'], (isfounder() ? '' : $_G['username']));
	}
	if(!empty($_GET['newmessage'])) {
		$newaccess = 0;
		$_GET['newexpiration'] = TIMESTAMP + (intval($_GET['newexpiration']) > 0 ? intval($_GET['newexpiration']) : 30) * 86400;
		$_GET['newmessage'] = nl2br(dhtmlspecialchars($_GET['newmessage']));
		$data = array(
			'admin' => $_G['username'],
			'access' => 0,
			'adminid' => $_G['adminid'],
			'dateline' => $_G['timestamp'],
			'expiration' => $_GET['newexpiration'],
			'message' => $_GET['newmessage'],
		);
		C::t('common_adminnote')->insert($data);
	}
}

require_once libfile('function/cloudaddons');
$newversion = (CHARSET == 'utf-8') ? dunserialize($_G['setting']['cloudaddons_newversion']) : json_decode($_G['setting']['cloudaddons_newversion'], true);
if(empty($newversion['newversion']) || !is_array($newversion['newversion']) || abs($_G['timestamp'] - $newversion['updatetime']) > 86400 || (isset($_GET['checknewversion']) && $_G['formhash'] == $_GET['formhash'])) {
	$newversion = json_decode(cloudaddons_open('&mod=app&ac=upgrade'), true);
	if(!empty($newversion['newversion'])) {
		$newversion['updatetime'] = $_G['timestamp'];
		C::t('common_setting')->update_setting('cloudaddons_newversion', ((CHARSET == 'utf-8') ? $newversion : json_encode($newversion)));
		updatecache('setting');
	} else {
		$newversion = array();
	}
}

$reldisp = is_numeric(DISCUZ_RELEASE) ? ('Release '.DISCUZ_RELEASE) : DISCUZ_RELEASE;

cpheader();
shownav();

show_user_bar();
show_todo();
show_security_advise();
show_releasetips();
show_envcheck();

echo '<div class="drow">';

echo '<div class="dcol d-23">';
show_sysinfo();
show_news();
echo '</div>';

echo '<div class="dcol d-13">';
show_widgets();
echo '</div>';

echo '</div>';

show_git_thanks();
show_forever_thanks();

echo '</div>';

$now = date('Y');
echo <<<EOT
<div class="copyright">
<p>Powered by <a href="http://www.discuz.net/" target="_blank" class="lightlink2">Discuz!</a> {$_G['setting']['version']}</p>
<p>Copyright &copy; 2001-$now Tencent Cloud.</p>
</div>
EOT;

function show_user_bar() {
	global $_G;
	if(isfounder()) {
		$cpadmingroup = cplang('founder_admin');
	} else {
		if($GLOBALS['admincp']->adminsession['cpgroupid']) {
			$cpgroup = C::t('common_admincp_group')->fetch($GLOBALS['admincp']->adminsession['cpgroupid']);
			$cpadmingroup = $cpgroup['cpgroupname'];
		} else {
			$cpadmingroup = cplang('founder_master');
		}
	}

	showsubmenu('home_welcome', array(), '</div><div class="dbox hometop">'.avatar(getglobal('uid'), 'middle', array('class' => 'avt')).'
	<div class="hinfo">
		<h4>'.cplang('home_welcome_txt').$_G['member']['username'].'</h4>
		<p>'.$cpadmingroup.'&nbsp;|&nbsp;'.cplang('home_mods').': <span id="mod_num">0</span></p>
	</div>', array('bbname' => $_G['setting']['bbname']));
}

function show_security_advise() {
	if(!isfounder()) {
		return;
	}
	global $lang, $_G;

	$securityadvise = '';
	$securityadvise .= !$_G['config']['admincp']['founder'] ? $lang['home_security_nofounder'] : '';
	$securityadvise .= !$_G['config']['admincp']['checkip'] ? $lang['home_security_checkip'] : '';
	$securityadvise .= $_G['config']['admincp']['runquery'] ? $lang['home_security_runquery'] : '';
	if($securityadvise) {
		showtableheader('home_security_tips', '', '', 0);
		showtablerow('', 'class="tipsblock"', '<ul>'.$securityadvise.'</ul>');
		showtablefooter();
	}
}

function show_todo() {
	global $_G;

	$membersmod = C::t('common_member_validate')->count_by_status(0);
	$threadsdel = C::t('forum_thread')->count_by_displayorder(-1);
	$groupmod = C::t('forum_forum')->validate_level_num();
	$reportcount = C::t('common_report')->fetch_count();

	$modcount = array();
	foreach (C::t('common_moderate')->count_group_idtype_by_status(0) as $value) {
		$modcount[$value['idtype']] = $value['count'];
	}

	$medalsmod = C::t('forum_medallog')->count_by_type(2);
	$threadsmod = $modcount['tid'];
	$postsmod = $modcount['pid'];
	$blogsmod = $modcount['blogid'];
	$doingsmod = $modcount['doid'];
	$picturesmod = $modcount['picid'];
	$sharesmod = $modcount['sid'];
	$commentsmod = $modcount['uid_cid'] + $modcount['blogid_cid'] + $modcount['sid_cid'] + $modcount['picid_cid'];
	$articlesmod = $modcount['aid'];
	$articlecommentsmod = $modcount['aid_cid'];
	$topiccommentsmod = $modcount['topicid_cid'];
	$verify = '';
	foreach (C::t('common_member_verify_info')->group_by_verifytype_count() as $value) {
		if($value['num']) {
			if($value['verifytype']) {
				$verifyinfo = !empty($_G['setting']['verify'][$value['verifytype']]) ? $_G['setting']['verify'][$value['verifytype']] : array();
				if($verifyinfo['available']) {
					$verify .= '<a href="'.ADMINSCRIPT.'?action=verify&operation=verify&do='.$value['verifytype'].'">'.cplang('home_mod_verify_prefix').$verifyinfo['title'].'</a>(<em class="lightnum">'.$value['num'].'</em>)';
				}
			} else {
				$verify .= '<a href="'.ADMINSCRIPT.'?action=verify&operation=verify&do=0">'.cplang('home_mod_verify_prefix').cplang('members_verify_profile').'</a>(<em class="lightnum">'.$value['num'].'</em>)';
			}
		}
	}

	$modtotalnum = intval($membersmod + $threadsmod + $postsmod + $medalsmod + $blogsmod + $picturesmod + $doingsmod + $sharesmod + $commentsmod + $articlesmod + $articlecommentsmod + $topiccommentsmod + $reportcount + $threadsdel);
	if($modtotalnum > 0) {
		echo '<script>$(\'mod_num\').innerHTML = \''.$modtotalnum.'\';</script>';
	}
	if($membersmod || $threadsmod || $postsmod || $medalsmod || $blogsmod || $picturesmod || $doingsmod || $sharesmod || $commentsmod || $articlesmod || $articlecommentsmod || $topiccommentsmod || $reportcount || $threadsdel || !empty($verify)) {
		showboxheader('', '', '', 1);
		echo '<div class="tipsbody"><div class="left tipicon"><svg width="20" height="20" fill="#1f7244" viewBox="0 0 16 16">
			<path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1 0 .707c-.48.48-1.072.588-1.503.588-.177 0-.335-.018-.46-.039l-3.134 3.134a5.927 5.927 0 0 1 .16 1.013c.046.702-.032 1.687-.72 2.375a.5.5 0 0 1-.707 0l-2.829-2.828-3.182 3.182c-.195.195-1.219.902-1.414.707-.195-.195.512-1.22.707-1.414l3.182-3.182-2.828-2.829a.5.5 0 0 1 0-.707c.688-.688 1.673-.767 2.375-.72a5.922 5.922 0 0 1 1.013.16l3.134-3.133a2.772 2.772 0 0 1-.04-.461c0-.43.108-1.022.589-1.503a.5.5 0 0 1 .353-.146z"></path></svg></div><h3 class="left margintop">'.cplang('home_mods').': </h3><p class="left difflink">'.
			($membersmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=members">'.cplang('home_mod_members').'</a>(<em class="lightnum">'.$membersmod.'</em>)' : '').
			($threadsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=threads&dateline=all">'.cplang('home_mod_threads').'</a>(<em class="lightnum">'.$threadsmod.'</em>)' : '').
			($postsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=replies&dateline=all">'.cplang('home_mod_posts').'</a>(<em class="lightnum">'.$postsmod.'</em>)' : '').
			($medalsmod ? '<a href="'.ADMINSCRIPT.'?action=medals&operation=mod">'.cplang('home_mod_medals').'</a>(<em class="lightnum">'.$medalsmod.'</em>)' : '').
			($groupmod ? '<a href="'.ADMINSCRIPT.'?action=group&operation=mod">'.cplang('group_mod_wait').'</a>(<em class="lightnum">'.$groupmod.'</em>)' : '').
			($blogsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=blogs&dateline=all">'.cplang('home_mod_blogs').'</a>(<em class="lightnum">'.$blogsmod.'</em>)' : '').
			($picturesmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=pictures&dateline=all">'.cplang('home_mod_pictures').'</a>(<em class="lightnum">'.$picturesmod.'</em>)' : '').
			($doingsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=doings&dateline=all">'.cplang('home_mod_doings').'</a>(<em class="lightnum">'.$doingsmod.'</em>)' : '').
			($sharesmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=shares&dateline=all">'.cplang('home_mod_shares').'</a>(<em class="lightnum">'.$sharesmod.'</em>)' : '').
			($commentsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=comments&dateline=all">'.cplang('home_mod_comments').'</a>(<em class="lightnum">'.$commentsmod.'</em>)' : '').
			($articlesmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=articles&dateline=all">'.cplang('home_mod_articles').'</a>(<em class="lightnum">'.$articlesmod.'</em>)' : '').
			($articlecommentsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=articlecomments&dateline=all">'.cplang('home_mod_articlecomments').'</a>(<em class="lightnum">'.$articlecommentsmod.'</em>)' : '').
			($topiccommentsmod ? '<a href="'.ADMINSCRIPT.'?action=moderate&operation=topiccomments&dateline=all">'.cplang('home_mod_topiccomments').'</a>(<em class="lightnum">'.$topiccommentsmod.'</em>)' : '').
			($reportcount ? '<a href="'.ADMINSCRIPT.'?action=report">'.cplang('home_mod_reports').'</a>(<em class="lightnum">'.$reportcount.'</em>)' : '').
			($threadsdel ? '<a href="'.ADMINSCRIPT.'?action=recyclebin">'.cplang('home_del_threads').'</a>(<em class="lightnum">'.$threadsdel.'</em>)' : '').
			$verify.
			'</p><div class="clear"></div>';
		showboxfooter();
	}
}

function show_releasetips() {
	global $reldisp, $_G;

	$siteuniqueid = C::t('common_setting')->fetch_setting('siteuniqueid');
	if(empty($siteuniqueid) || strlen($siteuniqueid) < 16) {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$siteuniqueid = 'DX'.$chars[date('y') % 60].$chars[date('n')].$chars[date('j')].$chars[date('G')].$chars[date('i')].$chars[date('s')].substr(md5($_G['clientip'].$_G['username'].TIMESTAMP), 0, 4).random(4);
		C::t('common_setting')->update_setting('siteuniqueid', $siteuniqueid);
		require_once libfile('function/cache');
		updatecache('setting');
	}

	if(!empty($_GET['closesitereleasetips'])) {
		C::t('common_setting')->update('sitereleasetips', 0);
		$sitereleasetips = 0;
		require_once libfile('function/cache');
		updatecache('setting');
	} else {
		$sitereleasetips = C::t('common_setting')->fetch('sitereleasetips');
	}

	$siterelease = C::t('common_setting')->fetch('siterelease');
	$releasehash = substr(hash('sha512', $_G['config']['security']['authkey'].DISCUZ_VERSION.DISCUZ_RELEASE.$siteuniqueid), 0, 32);
	if(empty($siterelease) || strcmp($siterelease, $releasehash) !== 0) {
		C::t('common_setting')->update('siteversion', DISCUZ_VERSION);
		C::t('common_setting')->update('siterelease', $releasehash);
		C::t('common_setting')->update('sitereleasetips', 1);
		$sitereleasetips = 1;
		require_once libfile('function/cache');
		updatecache('setting');
	}

	if($sitereleasetips) {
		showboxheader('version_tips', '', 'id="version_tips"');
		echo '<em class="unknown">'.lang("admincp", "version_tips_msg", array('ADMINSCRIPT' => ADMINSCRIPT, 'version' => constant("DISCUZ_VERSION").' '.$reldisp)).'</em>';
		showboxfooter();
	}
}

function show_onlines() {
	$admincp_session = C::t('common_admincp_session')->fetch_all_by_panel(1);
	if(count($admincp_session) == 1) {
		return;
	}
	$onlines = '';
	$members = C::t('common_member')->fetch_all(array_keys($admincp_session), false, 0);
	foreach ($admincp_session as $uid => $online) {
		$onlines .= '<a href="home.php?mod=space&uid='.$online['uid'].'" title="'.dgmdate($online['dateline']).'" target="_blank">'.$members[$uid]['username'].'</a>&nbsp;&nbsp;&nbsp;';
	}
	showboxheader('home_onlines', '', 'id="home_onlines"');
	echo $onlines;
	showboxfooter();
}

function show_note() {
	global $_G;

	showformheader('index');
	showboxheader('home_notes', '', 'id="home_notes"');

	$notemsghtml = '';
	foreach (C::t('common_adminnote')->fetch_all_by_access(0) as $note) {
		if($note['expiration'] < TIMESTAMP) {
			C::t('common_adminnote')->delete_note($note['id']);
		} else {
			$note['adminenc'] = rawurlencode($note['admin']);
			$note['expiration'] = ceil(($note['expiration'] - $note['dateline']) / 86400);
			$note['dateline'] = dgmdate($note['dateline'], 'dt');
			$notemsghtml .= '<div class="dcol"><div class="adminnote">'.'<a'.(isfounder() || $_G['member']['username'] == $note['admin'] ? ' href="'.ADMINSCRIPT.'?action=index&notesubmit=yes&noteid='.$note['id'].'" title="'.cplang('delete').'" class="ndel"' : '').'></a>'.
				("<div><p><span class=\"bold\"><a href=\"home.php?mod=space&username={$note['adminenc']}\" target=\"_blank\">{$note['admin']}</a></span></p><p>{$note['dateline']}</p><p class=\"marginbot\">(".cplang('validity').": {$note['expiration']} ".cplang('days').")</p><p>{$note['message']}</p>").'</div></div></div>';
		}
	}

	if($notemsghtml) {
		echo '<div class="drow">'.$notemsghtml.'</div></div><div class="boxbody">';
	}

	showboxrow('style="align-items: center"', array('class="dcol lineheight"', 'class="dcol lineheight"'), array(
		cplang('home_notes_add'),
		'<input type="text" class="txt" name="newmessage" value="" style="width:300px;" />'.cplang('validity').': <input type="text" class="txt" name="newexpiration" value="30" style="width:60px;" />'.cplang('days').'&nbsp;<input name="notesubmit" value="'.cplang('submit').'" type="submit" class="btn" />'
	));
	showboxfooter();
	showformfooter();
}

function show_filecheck() {
	global $lang;

	if(!isfounder()) {
		return;
	}

	$filecheck = C::t('common_cache')->fetch('checktools_filecheck_result');
	if($filecheck) {
		list($modifiedfiles, $deletedfiles, $unknownfiles, $doubt) = unserialize($filecheck['cachevalue']);
		$filecheckresult = "<div><em class=\"edited\">{$lang['filecheck_modify']}<span class=\"bignum\">$modifiedfiles</span></em>".
			"<em class=\"del\">{$lang['filecheck_delete']}<span class=\"bignum\">$deletedfiles</span></em>".
			"<em class=\"unknown\">{$lang['filecheck_unknown']}<span class=\"bignum\">$unknownfiles</span></em>".
			"<em class=\"unknown\">{$lang['filecheck_doubt']}<span class=\"bignum\">$doubt</span></em></div><p>".
			$lang['filecheck_last_homecheck'].': '.dgmdate($filecheck['dateline'], 'u').' <a href="'.ADMINSCRIPT.'?action=checktools&operation=filecheck&step=3">['.$lang['filecheck_view_list'].']</a></p>';
	} else {
		$filecheckresult = '';
	}

	showboxheader($lang['nav_filecheck'].' <a href="javascript:;" onclick="ajaxget(\''.ADMINSCRIPT.'?action=checktools&operation=filecheck&homecheck=yes\', \'filecheck_div\')">['.$lang['filecheck_check_now'].']</a>', 'nobottom fixpadding', 'id="filecheck"');
	echo '<div id="filecheck_div">'.$filecheckresult.'</div>';
	showboxfooter();
	if(TIMESTAMP - $filecheck['dateline'] > 86400 * 7) {
		echo '<script>ajaxget(\''.ADMINSCRIPT.'?action=checktools&operation=filecheck&homecheck=yes\', \'filecheck_div\');</script>';
	}
}

function show_envcheck() {
	global $reldisp;

	$return = '';
	$env_ok = true;
	$now_ver_gd = function_exists('gd_info') ? gd_info() : false;
	$now_ver = array('PHP' => constant('PHP_VERSION'), 'MySQL' => helper_dbtool::dbversion(), 'XML' => function_exists('xml_parser_create'), 'JSON' => function_exists('json_encode'), 'FileSock Function' => (function_exists('fsockopen') || function_exists('pfsockopen') || function_exists('stream_socket_client') || function_exists('curl_init')), 'GD' => ($now_ver_gd ? preg_replace('/[^0-9.]+/', '', $now_ver_gd['GD Version']) : false));
	$req_ver = array('PHP' => '5.6.0', 'MySQL' => '5.5.3', 'XML' => true, 'JSON' => true, 'FileSock Function' => true, 'GD' => '1.0');
	$sug_ver = array('PHP' => '7.3.0', 'MySQL' => '5.7.0', 'XML' => true, 'JSON' => true, 'FileSock Function' => true, 'GD' => '2.0');
	foreach ($now_ver as $key => $value) {
		if($req_ver[$key] === true) {
			if(!$value) {
				$return .= '<em class="unfixed">'.lang("admincp", "req_not_found", array('req' => $key)).'</em>';
				$env_ok = false;
			}
		} elseif(version_compare($value, $req_ver[$key], '<')) {
			$return .= '<em class="unfixed">'.lang("admincp", "req_ver_too_low", array('req' => $key, 'now_ver' => $value, 'sug_ver' => $sug_ver[$key], 'req_ver' => $req_ver[$key])).'</em>';
			$env_ok = false;
		}
	}
	if(!$env_ok) {
		showboxheader('detect_environment', '', 'id="detect_environment"');
		echo $return;
		showboxfooter();
	}
}

function show_sysinfo() {
	global $newversion, $reldisp, $lang, $_G;

	loaducenter();

	if(empty($newversion['newversion']['qqqun'])) {
		$newversion['newversion']['qqqun'] = '73'.'210'.'36'.'90';
	}

	showboxheader('home_sys_info', 'listbox', 'id="home_sys_info"');
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_discuz_version'),
		'Discuz! '.DISCUZ_VERSION.' '.$reldisp.' '.strtoupper(CHARSET).((strlen(DISCUZ_RELEASE) == 8 && DISCUZ_RELEASE != '20180101') ? '' : cplang('home_git_version'))
	));

	$newversion['newversion'] = !empty($newversion['newversion']) ? $newversion['newversion'] : array();
	$reldisp_addon = is_numeric($newversion['newversion']['release']) ? ('Release '.$newversion['newversion']['release']) : $newversion['newversion']['release'];

	$downlist = array();
	foreach ($newversion['newversion']['downlist'] as $key => $value) {
		$downlist[] = '<a href="'.diconv($value['url'], 'utf-8', CHARSET).'" target="_blank">'.discuzcode(strip_tags(diconv($value['title'], 'utf-8', CHARSET)), 1, 0).'</a>';
	}

	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_check_newversion'),
		($newversion['newversion']['release'] ? ($newversion['newversion']['release'] != DISCUZ_RELEASE ? '<b style="color:red;">' : '').'Discuz! '.$newversion['newversion']['version'].' '.$reldisp_addon.' '.strtoupper(CHARSET).' '.($newversion['newversion']['release'] != DISCUZ_RELEASE ? '</b>' : '') : '<a href="https://www.dismall.com/thread-73-1-1.html" target="_blank">'.cplang('detect_environment_error').'</a>').
		' <a href="'.ADMINSCRIPT.'?action=index&checknewversion&formhash='.$_G['formhash'].'">[ '.cplang('refresh').' ]</a>&nbsp;&nbsp;<br><br>'.
		(!empty($downlist) ? implode('&#x3001;', $downlist).($newversion['newversion']['qqqun'] ? '<span class="bold">&nbsp;&nbsp;|&nbsp;&nbsp;'.cplang('qq_group').$newversion['newversion']['qqqun'].'</span>' : '') : '<span class="bold"><a href="https://gitee.com/3dming/DiscuzL/attach_files" target="_blank">'.cplang('download_latest').'</a> | '.cplang('qq_group').'73'.'21'.'03'.'690</span>')
	));
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_ucclient_version'),
		'UCenter '.UC_CLIENT_VERSION.' Release '.UC_CLIENT_RELEASE
	));
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_environment'),
		PHP_OS.' / PHP v'.PHP_VERSION
	));
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_serversoftware'),
		$_SERVER['SERVER_SOFTWARE']
	));
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_database'),
		helper_dbtool::dbversion()
	));
	if(@ini_get('file_uploads')) {
		require_once libfile('function/upload');
		$fileupload = getmaxupload();
	} else {
		$fileupload = '<font color="red">'.$lang['no'].'</font>';
	}
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_upload_perm'),
		$fileupload
	));
	$dbsize = helper_dbtool::dbsize();
	$dbsize = $dbsize ? sizecount($dbsize) : $lang['unknown'];
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_database_size'),
		$dbsize
	));
	if(isset($_GET['attachsize'])) {
		$attachsize = C::t('forum_attachment_n')->get_total_filesize();
		$attachsize = is_numeric($attachsize) ? sizecount($attachsize) : $lang['unknown'];
	} else {
		$attachsize = '<a href="'.ADMINSCRIPT.'?action=index&attachsize">[ '.$lang['detail'].' ]</a>';
	}
	showboxrow('', array('class="dcol lineheight d-14"', 'class="dcol lineheight d-1"'), array(
		cplang('home_attach_size'),
		$attachsize
	));
	showboxfooter();
}

function show_news() {
	global $newversion;

	showboxheader('discuz_news', 'listbox', 'id="discuz_news"');
	if(!empty($newversion['news'])) {
		$newversion['news'] = dhtmlspecialchars($newversion['news']);
		foreach ($newversion['news'] as $v) {
			showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol"'), array(
				'<a href="'.$v['url'].'" target="_blank">'.discuzcode(strip_tags(diconv($v['title'], 'utf-8', CHARSET)), 1, 0).'</a>',
				'['.discuzcode(strip_tags($v['date']), 1, 0).']',
			));
		}
	} else {
		showboxrow('', array('class="dcol d-1"', 'class="dcol td21" style="text-align:right;"'), array(
			'<a href="https://www.dismall.com/" target="_blank">'.cplang('log_in_to_update').'</a>',
			'',
		));
		showboxrow('', array('class="dcol d-1"', 'class="dcol td21" style="text-align:right;"'), array(
			'<a href="https://gitee.com/3dming/DiscuzL/attach_files" target="_blank">'.cplang('download_latest').'</a>',
			'',
		));
	}
	showboxfooter();
}

function show_widgets() {
	$widgets = array(
		array('.', 'show_onlines'),
		array('.', 'show_note'),
		array('.', 'show_filecheck'),
	);
	$plugins = C::t('common_plugin')->fetch_all_data();
	foreach ($plugins as $plugin) {
		$cpindexfile = DISCUZ_ROOT.'./source/plugin/'.$plugin['identifier'].'/admin/admin_widget.php';
		if(!file_exists($cpindexfile)) {
			continue;
		}
		$widgets[] = array($cpindexfile, 'widget_'.$plugin['identifier']);
	}

	foreach ($widgets as $widget) {
		list($file, $func) = $widget;
		if($file != '.') {
			require_once $file;
		}
		if(!function_exists($func)) {
			continue;
		}
		$func();
	}
}

function show_git_thanks() {
	showboxheader('contributors', 'fixpadding', 'id="contributors"');
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(
		cplang('home_git_thanks'),
		'
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=laozhoubuluo" class="lightlink2" target="_blank">&#x8001;&#x5468;&#x90E8;&#x843D;</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=popcorner" class="lightlink2" target="_blank">popcorner</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=oldhuhu" class="lightlink2" target="_blank">oldhuhu</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=zoewho" class="lightlink2" target="_blank">&#x6E56;&#x4E2D;&#x6C89;</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=3dming" class="lightlink2" target="_blank">&#x8BF8;&#x845B;&#x6653;&#x660E;</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=brotherand2" class="lightlink2" target="_blank">brotherand2</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=contributions" class="lightlink2" target="_blank">git</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=nftstudio" class="lightlink2" target="_blank">&#x9006;&#x98CE;&#x5929;</a>
				<a href="https://gitee.com/Discuz/DiscuzX/commits/master?user=ONEXIN" class="lightlink2" target="_blank">ONEXIN</a>'
	));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(
		'',
		'<a href="https://gitee.com/Discuz/DiscuzX/contributors?ref=master" class="lightlink2" target="_blank">'.cplang('contributors_see').'</a>'
	));
	showboxfooter();
}

function show_forever_thanks() {
	$copyRightMessage = array(
		'&#x7248;&#x6743;&#x6240;&#x6709;',
		'&#x817E;&#x8BAF;&#x4E91;&#x8BA1;&#x7B97;&#xFF08;&#x5317;&#x4EAC;&#xFF09;&#x6709;&#x9650;&#x8D23;&#x4EFB;&#x516C;&#x53F8;',
		'&#x627F;&#x63A5;&#x8FD0;&#x8425;',
		'&#x5408;&#x80A5;&#x8D30;&#x9053;&#x7F51;&#x7EDC;&#x79D1;&#x6280;&#x6709;&#x9650;&#x516C;&#x53F8;',
	);
	showboxheader('home_dev', 'fixpadding', 'id="home_dev"');
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array($copyRightMessage[0], '<span class="bold">'.$copyRightMessage[1].'</span>'));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array($copyRightMessage[2], '<span class="bold">'.$copyRightMessage[3].'</span>'));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(cplang('home_dev_manager'),
		'<a href="http://www.discuz.net/home.php?mod=space&uid=1" class="lightlink2 smallfont" target="_blank">'.cplang('dev_manager').'</a>'
	));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(cplang('home_dev_team'),
		'<a href="http://www.discuz.net/home.php?mod=space&uid=174393" class="lightlink2 smallfont" target="_blank">Guode \'sup\' Li</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=859" class="lightlink2 smallfont" target="_blank">Hypo \'Cnteacher\' Wang</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=263098" class="lightlink2 smallfont" target="_blank">Liming \'huangliming\' Huang</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=706770" class="lightlink2 smallfont" target="_blank">Jun \'Yujunhao\' Du</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=80629" class="lightlink2 smallfont" target="_blank">Ning \'Monkeye\' Hou</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=246213" class="lightlink2 smallfont" target="_blank">Lanbo Liu</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=322293" class="lightlink2 smallfont" target="_blank">Qingpeng \'andy888\' Zheng</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=401635" class="lightlink2 smallfont" target="_blank">Guosheng \'bilicen\' Zhang</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=2829" class="lightlink2 smallfont" target="_blank">Mengshu \'msxcms\' Chen</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=492114" class="lightlink2 smallfont" target="_blank">Liang \'Metthew\' Xu</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=1087718" class="lightlink2 smallfont" target="_blank">Yushuai \'Max\' Cong</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=875919" class="lightlink2 smallfont" target="_blank">Jie \'tom115701\' Zhang</a>
	'
	));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(cplang('home_dev_skins'),
		'<a href="http://www.discuz.net/home.php?mod=space&uid=294092" class="lightlink2 smallfont" target="_blank">Fangming \'Lushnis\' Li</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=674006" class="lightlink2 smallfont" target="_blank">Jizhou \'Iavav\' Yuan</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=717854" class="lightlink2 smallfont" target="_blank">Ruitao \'Pony.M\' Ma</a>'
	));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team"'), array(cplang('home_dev_thanks'),
		'<a href="https://gitee.com/Discuz/DiscuzX/contributors?ref=master" class="lightlink2 smallfont" target="_blank">Discuz! X Git Contributors</a>
	<a href="http://www.discuzfans.com" class="lightlink2 smallfont" target="_blank">Discuz! Fans</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=122246" class="lightlink2 smallfont" target="_blank">Heyond</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=632268" class="lightlink2 smallfont" target="_blank">JinboWang</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=15104" class="lightlink2 smallfont" target="_blank">Redstone</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=10407" class="lightlink2 smallfont" target="_blank">Qiang Liu</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=210272" class="lightlink2 smallfont" target="_blank">XiaoDunFang</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=86282" class="lightlink2 smallfont" target="_blank">Jianxieshui</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=9600" class="lightlink2 smallfont" target="_blank">Theoldmemory</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=2629" class="lightlink2 smallfont" target="_blank">Rain5017</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=26926" class="lightlink2 smallfont" target="_blank">Snow Wolf</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=17149" class="lightlink2 smallfont" target="_blank">Hehechuan</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=9132" class="lightlink2 smallfont" target="_blank">Pk0909</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=248" class="lightlink2 smallfont" target="_blank">feixin</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=675" class="lightlink2 smallfont" target="_blank">Laobing Jiuba</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=13877" class="lightlink2 smallfont" target="_blank">Artery</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=233" class="lightlink2 smallfont" target="_blank">Huli Hutu</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=122" class="lightlink2 smallfont" target="_blank">Lao Gui</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=159" class="lightlink2 smallfont" target="_blank">Tyc</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=177" class="lightlink2 smallfont" target="_blank">Stoneage</a>
	<a href="http://www.discuz.net/home.php?mod=space&uid=7155" class="lightlink2 smallfont" target="_blank">Gregry</a>'
	));
	showboxrow('', array('class="dcol d-1 lineheight"', 'class="dcol lineheight team tm"'), array(cplang('home_dev_links'), '<a href="https://gitee.com/Discuz/DiscuzX" class="lightlink2" target="_blank">Discuz! X Git</a>,&nbsp;
	<a href="https://www.dismall.com/" class="lightlink2" target="_blank">'.cplang('app_discussion').'</a>,&nbsp; 
	<a href="https://www.discuz.net/" class="lightlink2" target="_blank">'.cplang('discussion_area').'</a>, &nbsp;
	<a href="'.ADMINSCRIPT.'?action=cloudaddons" class="lightlink2" target="_blank">'.cplang('app_center').'</a>'));
	showboxfooter();
}