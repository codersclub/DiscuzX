<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_mobile.php 36284 2016-12-12 00:47:50Z nemohou $
 */
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if($mod == 'mobile' && defined('IN_MOBILE')) {
	if($_G['setting']['domain']['app']['mobile']) {
		dheader('Location:'.$_G['scheme'].'://'.$_G['setting']['domain']['app']['mobile']);
	} else {
		dheader('Location:'.$_G['siteurl'].'forum.php?mobile=yes');
	}
} elseif(!$_G['setting']['mobile']['allowmobile']) {
	dheader("Location:".($_G['setting']['domain']['app']['default'] ? $_G['scheme'].'://'.$_G['setting']['domain']['app']['default'] : $_G['siteurl']));
}
include DISCUZ_ROOT.'./source/language/touch/lang_template.php';
$_G['lang'] = array_merge($_G['lang'], $lang);
$navtitle = $_G['lang']['misc_mobile_title'];
if(getgpc('view') == true) {
	include libfile('function/forumlist');
	loadcache('userstats');
	loadcache('historyposts');
	$postdata = $_G['cache']['historyposts'] ? explode("\t", $_G['cache']['historyposts']) : array(0,0);
	$postdata[0] = intval($postdata[0]);
	$postdata[1] = intval($postdata[1]);

	$query = C::t('forum_forum')->fetch_all_forum(1);
	$threads = $posts = $todayposts = 0;
	foreach($query as $forum) {
		if($forum['type'] != 'group') {
			$threads += $forum['threads'];
			$posts += $forum['posts'];
			$todayposts += $forum['todayposts'];

			if($forum['type'] == 'forum' && isset($catlist[$forum['fup']])) {
				if(forum($forum)) {
					$catlist[$forum['fup']]['forums'][] = $forum['fid'];
					$forum['orderid'] = $catlist[$forum['fup']]['forumscount']++;
					$forum['subforums'] = '';
					$forumlist[$forum['fid']] = $forum;
				}

			} elseif(isset($forumlist[$forum['fup']])) {
				$forumlist[$forum['fup']]['threads'] += $forum['threads'];
				$forumlist[$forum['fup']]['posts'] += $forum['posts'];
				$forumlist[$forum['fup']]['todayposts'] += $forum['todayposts'];
			}

		} else {
			$forum['forumscount'] 	= 0;
			$catlist[$forum['fid']] = $forum;
		}
	}
	$_GET['forumlist'] = 1;
	define('IN_MOBILE',2);
	define('IN_PREVIEW',1);
	ob_start();
	include template('forum/discuz');
} else {
	if(getglobal('setting/domain/app/mobile')) {
		$url = $_G['scheme'].'://'.$_G['setting']['domain']['app']['mobile'];
		$file = 'newmobiledomain.png';
	} else {
		$url = $_G['siteurl'];
		$file = 'newmobile.png';
	}
	$qrimg = DISCUZ_ROOT.'./data/cache/'.$file;
	if(!file_exists($qrimg)) {
		require_once DISCUZ_ROOT.'source/plugin/mobile/qrcode.class.php';
		QRcode::png($url, $qrimg, QR_ECLEVEL_Q, 4);
	}
	include template('touch/common/preview');
}
function output_preview() {
	$content = ob_get_contents();
	ob_end_clean();
	ob_start();
	$content = preg_replace_callback("/(\<a[^\>]+href=\").*?(\"[^\>]*\>)/", 'output_preview_callback_replace_href_21', $content);
	$content = preg_replace("/\<script.+?\<\/script\>/", '', $content);
	$content = str_replace('</body>' , '<script>document.querySelectorAll(\'a\').forEach(function (a) {a.addEventListener(\'click\', function (e) {e.preventDefault();return false;});})</script></body>', $content);
	echo $content;
	exit;
}

function output_preview_callback_replace_href_21($matches) {
	return $matches[1].'misc.php?mod=mobile&view=true'.$matches[2];;
}

?>