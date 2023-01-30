<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_misc.php 33487 2013-06-21 08:00:39Z kamichen $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function convertip($ip) {
	return ip::convert($ip);
}

function procthread($thread, $timeformat = 'd') {
	global $_G;

	$lastvisit = $_G['member']['lastvisit'];
	if(empty($_G['forum_colorarray'])) {
		$_G['forum_colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');
	}

	if($thread['closed']) {
		$thread['new'] = 0;
		if($thread['isgroup'] && $thread['closed'] > 1) {
			$thread['folder'] = 'common';
		} else {
			$thread['folder'] = 'lock';
		}
	} else {
		$thread['folder'] = 'common';
		if($lastvisit < $thread['lastpost'] && (empty($_G['cookie']['oldtopics']) || strpos($_G['cookie']['oldtopics'], 'D'.$thread['tid'].'D') === FALSE)) {
			$thread['new'] = 1;
			$thread['folder'] = 'new';
		} else {
			$thread['new'] = 0;
		}
	}

	$thread['icon'] = '';
	$thread['id'] = random(6, 1);
	if(!$thread['forumname']) {
		$thread['forumname'] = empty($_G['cache']['forums'][$thread['fid']]['name']) ? 'Forum' : $_G['cache']['forums'][$thread['fid']]['name'];
	}
	$thread['dateline'] = dgmdate($thread['dateline'], $timeformat);
	$thread['lastpost'] = dgmdate($thread['lastpost'], 'u');
	$thread['lastposterenc'] = rawurlencode($thread['lastposter']);

	if($thread['replies'] > $thread['views']) {
		$thread['views'] = $thread['replies'];
	}

	$postsnum = $thread['special'] ? $thread['replies'] : $thread['replies'] + 1;
	$pagelinks = '';
	if($postsnum  > $_G['ppp']) {
		if($_G['setting']['domain']['app']['forum'] || $_G['setting']['domain']['app']['default']) {
			$domain = $_G['scheme'].'://'.($_G['setting']['domain']['app']['forum'] ? $_G['setting']['domain']['app']['forum'] : ($_G['setting']['domain']['app']['default'] ? $_G['setting']['domain']['app']['default'] : '')).'/';
		} else {
			$domain = $_G['siteurl'];
		}
		$posts = $postsnum;
		$topicpages = ceil($posts / $_G['ppp']);
		for($i = 1; $i <= $topicpages; $i++) {
			if(!is_array($_G['setting']['rewritestatus']) || !in_array('forum_viewthread', $_G['setting']['rewritestatus'])) {
				$pagelinks .= '<a href="forum.php?mod=viewthread&tid='.$thread['tid'].'&page='.$i.($_GET['from'] ? '&from='.$_GET['from'] : '').'" target="_blank">'.$i.'</a> ';
			} else {
				$pagelinks .= '<a href="'.rewriteoutput('forum_viewthread', 1, $domain, $thread['tid'], $i, '', '').'" target="_blank">'.$i.'</a> ';
			}
			if($i == 6) {
				$i = $topicpages + 1;
			}
		}
		if($topicpages > 6) {
			if(!is_array($_G['setting']['rewritestatus']) || !in_array('forum_viewthread', $_G['setting']['rewritestatus'])) {
				$pagelinks .= ' .. <a href="forum.php?mod=viewthread&tid='.$thread['tid'].'&page='.$topicpages.'" target="_blank">'.$topicpages.'</a> ';
			} else {
				$pagelinks .= ' .. <a href="'.rewriteoutput('forum_viewthread', 1, $domain, $thread['tid'], $topicpages, '', '').'" target="_blank">'.$topicpages.'</a> ';
			}
		}
		$thread['multipage'] = '... '.$pagelinks;
	} else {
		$thread['multipage'] = '';
	}

	if($thread['highlight']) {
		$string = sprintf('%02d', $thread['highlight']);
		$stylestr = sprintf('%03b', $string[0]);

		$thread['highlight'] = 'style="';
		$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
		$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
		$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
		$thread['highlight'] .= $string[1] ? 'color: '.$_G['forum_colorarray'][$string[1]] : '';
		$thread['highlight'] .= '"';
	} else {
		$thread['highlight'] = '';
	}

	return $thread;
}

function modlog($thread, $action) {
	global $_G;
	$reason = $_GET['reason'];
	writelog('modslog', dhtmlspecialchars("{$_G['timestamp']}\t{$_G['username']}\t{$_G['adminid']}\t{$_G['clientip']}\t".$_G['forum']['fid']."\t".$_G['forum']['name']."\t{$thread['tid']}\t{$thread['subject']}\t$action\t$reason\t".$_G['toforum']['fid']."\t".$_G['toforum']['name']));
}

function checkreasonpm() {
	global $_G;
	$reason = trim(strip_tags($_GET['reason']));
	if(($_G['group']['reasonpm'] == 1 || $_G['group']['reasonpm'] == 3) && !$reason) {
		showmessage('admin_reason_invalid');
	}
	return $reason;
}

function sendreasonpm($var, $item, $notevar, $notictype = '', $system = 1) {
	global $_G;
	if(!empty($var['authorid']) && $var['authorid'] != $_G['uid']) {
		if(!empty($notevar['modaction'])) {
			$notevar['from_id'] = 0;
			$notevar['from_idtype'] = 'moderate_'.$notevar['modaction'];
			$notevar['modaction'] = lang('forum/modaction', $notevar['modaction']);
		}
		empty($notictype) && $notictype = 'system';
		notification_add($var['authorid'], $notictype, $item, $notevar, $system);
	}
}

function modreasonselect($isadmincp = 0, $reasionkey = 'modreasons') {
	global $_G;
	if(!isset($_G['cache'][$reasionkey]) || !is_array($_G['cache'][$reasionkey])) {
		loadcache(array($reasionkey, 'stamptypeid'));
	}
	$select = '';
	if(!empty($_G['cache'][$reasionkey])) {
		foreach($_G['cache'][$reasionkey] as $reason) {
			$select .= !$isadmincp ? ($reason ? '<li>'.$reason.'</li>' : '<li>--------</li>') : ($reason ? '<option value="'.dhtmlspecialchars($reason).'">'.$reason.'</option>' : '<option></option>');
		}
	}
	if($select) {
		return $select;
	} else {
		return false;
	}

}



function acpmsg($message, $url = '', $type = '', $extra = '') {
	if(defined('IN_ADMINCP')) {
		!defined('CPHEADER_SHOWN') && cpheader();
		cpmsg($message, $url, $type, $extra);
	} else {
		showmessage($message, $url, $extra);
	}
}

function savebanlog($username, $origgroupid, $newgroupid, $expiration, $reason) {
	global $_G;
	if($_G['setting']['plugins']['func'][HOOKTYPE]['savebanlog']) {
		$param = func_get_args();
		hookscript('savebanlog', 'global', 'funcs', array('param' => $param), 'savebanlog');
	}
	writelog('banlog', dhtmlspecialchars("{$_G['timestamp']}\t{$_G['member']['username']}\t{$_G['groupid']}\t{$_G['clientip']}\t$username\t$origgroupid\t$newgroupid\t$expiration\t$reason"));
}

function clearlogstring($str) {
	if(!empty($str)) {
		if(!is_array($str)) {
			$str = dhtmlspecialchars(trim($str));
			$str = str_replace(array("\t", "\r\n", "\n", "   ", "  "), ' ', $str);
		} else {
			foreach ($str as $key => $val) {
				$str[$key] = clearlogstring($val);
			}
		}
	}
	return $str;
}

function implodearray($array, $skip = array()) {
	$return = '';
	if(is_array($array) && !empty($array)) {
		foreach ($array as $key => $value) {
			if(empty($skip) || !in_array($key, $skip, true)) {
				if(is_array($value)) {
					$return .= "$key={".implodearray($value, $skip)."}; ";
				} elseif(!empty($value)) {
					$return .= "$key=$value; ";
				} else {
					$return .= '';
				}
			}
		}
	}
	return $return;
}

function undeletethreads($tids) {
	global $_G;
	if($_G['setting']['plugins']['func'][HOOKTYPE]['undeletethreads']) {
		$param = func_get_args();
		hookscript('undeletethreads', 'global', 'funcs', array('param' => $param), 'undeletethreads');
	}
	$threadsundel = 0;
	if($tids && is_array($tids)) {
		$arrtids = $tids;
		$tids = '\''.implode('\',\'', $tids).'\'';

		$tuidarray = $ruidarray = $fidarray = $posttabletids = array();
		foreach(C::t('forum_thread')->fetch_all_by_tid($arrtids) as $thread) {
			$posttabletids[$thread['posttableid'] ? $thread['posttableid'] : 0][] = $thread['tid'];
		}
		foreach($posttabletids as $posttableid => $ptids) {
			foreach(C::t('forum_post')->fetch_all_by_tid($posttableid, $ptids, false) as $post) {
				if($post['first']) {
					$tuidarray[$post['fid']][] = $post['authorid'];
				} else {
					$ruidarray[$post['fid']][] = $post['authorid'];
				}
				if(!in_array($post['fid'], $fidarray)) {
					$fidarray[] = $post['fid'];
				}
			}
			C::t('forum_post')->update_by_tid($posttableid, $ptids, array('invisible' => '0'), true);
		}
		if($tuidarray) {
			foreach($tuidarray as $fid => $tuids) {
				updatepostcredits('+', $tuids, 'post', $fid);
			}
		}
		if($ruidarray) {
			foreach($ruidarray as $fid => $ruids) {
				updatepostcredits('+', $ruids, 'reply', $fid);
			}
		}

		$threadsundel = C::t('forum_thread')->update($arrtids, array('displayorder'=>0, 'moderated'=>1));

		updatemodlog($tids, 'UDL');
		updatemodworks('UDL', $threadsundel);

		foreach($fidarray as $fid) {
			updateforumcount($fid);
		}
	}
	return $threadsundel;
}

function recyclebinpostdelete($deletepids, $posttableid = false) {
	if(empty($deletepids)) {
		return 0;
	}

	require_once libfile('function/delete');
	return deletepost($deletepids, 'pid', true, $posttableid);
}

function recyclebinpostundelete($undeletepids, $posttableid = false) {
	global $_G;
	if($_G['setting']['plugins']['func'][HOOKTYPE]['recyclebinpostundelete']) {
		$param = func_get_args();
		hookscript('recyclebinpostundelete', 'global', 'funcs', array('param' => $param), 'recyclebinpostundelete');
	}
	$postsundel = 0;
	if(empty($undeletepids)) {
		return $postsundel;
	}


	loadcache('posttableids');
	$posttableids = !empty($_G['cache']['posttableids']) ? ($posttableid !== false && in_array($posttableid, $_G['cache']['posttableids']) ? array($posttableid) : $_G['cache']['posttableids']): array('0');

	$postarray = $ruidarray = $fidarray = $tidarray = array();
	foreach($posttableids as $ptid) {
		foreach(C::t('forum_post')->fetch_all_post($ptid, $undeletepids, false) as $post) {
			if(!$post['first']) {
				$ruidarray[$post['fid']][] = $post['authorid'];
			}
			$fidarray[$post['fid']] = $post['fid'];
			$tidarray[$post['tid']] = $post['tid'];

		}
	}
	if(empty($fidarray)) {
		return $postsundel;
	}

	C::t('forum_post')->update_post($posttableid, $undeletepids, array('invisible' => '0'), true);

	include_once libfile('function/post');
	if($ruidarray) {
		foreach($ruidarray as $fid => $ruids) {
			updatepostcredits('+', $ruids, 'reply', $fid);
		}
	}
	foreach($tidarray as $tid) {
		updatethreadcount($tid, 1);
	}
	foreach($fidarray as $fid) {
		updateforumcount($fid);
	}

	return count($undeletepids);
}

function process_ipnotice($ipconverted) {
	if(!$ipconverted) {
		return '';
	}

	$ipconverted = substr($ipconverted, 1);
	if(strpos($ipconverted, '-') !== false) {
		$ipconverted = substr($ipconverted, 0, strpos($ipconverted, '-'));
	}

	$ipconverted = trim($ipconverted);

	return '- '.$ipconverted ;
}

?>