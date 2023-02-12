<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_forumlist.php 31960 2012-10-26 06:27:50Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function checkautoclose($thread) {
	global $_G;

	if(!$_G['forum']['ismoderator'] && $_G['forum']['autoclose']) {
		$closedby = $_G['forum']['autoclose'] > 0 ? 'dateline' : 'lastpost';
		if(TIMESTAMP - $thread[$closedby] > abs($_G['forum']['autoclose']) * 86400) {
			return 'post_thread_closed_by_'.$closedby;
		}
	}
	return FALSE;
}

function forum(&$forum) {
	global $_G;
	$lastvisit = $_G['member']['lastvisit'];
	if(!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || !empty($forum['allowview']) || (isset($forum['users']) && strstr($forum['users'], "\t{$_G['uid']}\t"))) {
		$forum['permission'] = 2;
	} elseif(!$_G['setting']['hideprivate']) {
		$forum['permission'] = 1;
	} else {
		return FALSE;
	}

	if($forum['icon']) {
		$forum['icon'] = get_forumimg($forum['icon']);
		$forum['icon'] = '<a href="forum.php?mod=forumdisplay&fid='.$forum['fid'].'"><img src="'.$forum['icon'].'" '.(!empty($forum['extra']['iconwidth']) ? 'style="width: '.$forum['extra']['iconwidth'].'px;"' : '').' align="left" alt="'.$forum['name'].'" /></a>';
	}

	$lastpost = array(0, 0, '', '');

	$forum['lastpost'] = is_string($forum['lastpost']) ? explode("\t", $forum['lastpost']) : $forum['lastpost'];

	$forum['lastpost'] =count($forum['lastpost']) != 4 ? $lastpost : $forum['lastpost'];

	list($lastpost['tid'], $lastpost['subject'], $lastpost['dateline'], $lastpost['author']) = $forum['lastpost'];
	$thisforumlastvisit = array();
	if(!empty($_G['cookie']['forum_lastvisit'])) {
		preg_match("/D\_".$forum['fid']."\_(\d+)/", $_G['cookie']['forum_lastvisit'], $thisforumlastvisit);
	}

	$forum['folder'] = ($thisforumlastvisit && $thisforumlastvisit[1] > $lastvisit ? $thisforumlastvisit[1] : $lastvisit) < $lastpost['dateline'] ? ' class="new"' : '';

	if($lastpost['tid']) {
		$lastpost['dateline'] = dgmdate($lastpost['dateline'], 'u');
		$lastpost['authorusername'] = $lastpost['author'];
		if($lastpost['author']) {
			$lastpost['author'] = '<a href="home.php?mod=space&username='.rawurlencode($lastpost['author']).'">'.$lastpost['author'].'</a>';
		}
		$forum['lastpost'] = $lastpost;
	} else {
		$forum['lastpost'] = $lastpost['authorusername'] = '';
	}

	$forum['moderators'] = moddisplay($forum['moderators'], $_G['setting']['moddisplay'], !empty($forum['inheritedmod']));

	if(isset($forum['subforums'])) {
		$forum['subforums'] = implode(', ', $forum['subforums']);
	}

	return TRUE;
}

function forumselect($groupselectable = FALSE, $arrayformat = 0, $selectedfid = 0, $showhide = FALSE, $evalue = FALSE, $special = 0) {
	global $_G;

	if(!isset($_G['cache']['forums'])) {
		loadcache('forums');
	}
	$forumcache = &$_G['cache']['forums'];
	$forumlist = $arrayformat ? array() : '<optgroup label="&nbsp;">';
	foreach($forumcache as $forum) {
		if(!$forum['status'] && !$showhide) {
			continue;
		}
		$selected = '';
		if($selectedfid) {
			if(!is_array($selectedfid)) {
				$selected = $selectedfid == $forum['fid'] ? ' selected' : '';
			} else {
				$selected = in_array($forum['fid'], $selectedfid) ? ' selected' : '';
			}
		}
		if($forum['type'] == 'group') {
			if($arrayformat) {
				$forumlist[$forum['fid']]['name'] = $forum['name'];
			} else {
				$forumlist .= $groupselectable ? '<option value="'.($evalue ? 'gid_' : '').$forum['fid'].'" class="bold">--'.$forum['name'].'</option>' : '</optgroup><optgroup label="--'.$forum['name'].'">';
			}
			$visible[$forum['fid']] = true;
		} elseif($forum['type'] == 'forum' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t{$_G['uid']}\t")) && (!$special || (substr($forum['allowpostspecial'], -$special, 1)))) {
			if($arrayformat) {
				$forumlist[$forum['fup']]['sub'][$forum['fid']] = $forum['name'];
			} else {
				$forumlist .= '<option value="'.($evalue ? 'fid_' : '').$forum['fid'].'"'.$selected.'>'.$forum['name'].'</option>';
			}
			$visible[$forum['fid']] = true;
		} elseif($forum['type'] == 'sub' && isset($visible[$forum['fup']]) && (!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || strstr($forum['users'], "\t{$_G['uid']}\t")) && (!$special || substr($forum['allowpostspecial'], -$special, 1))) {
			if($arrayformat) {
				$forumlist[$forumcache[$forum['fup']]['fup']]['child'][$forum['fup']][$forum['fid']] = $forum['name'];
			} else {
				$forumlist .= '<option value="'.($evalue ? 'fid_' : '').$forum['fid'].'"'.$selected.'>&nbsp; &nbsp; &nbsp; '.$forum['name'].'</option>';
			}
		}
	}
	if(!$arrayformat) {
		$forumlist .= '</optgroup>';
		$forumlist = str_replace('<optgroup label="&nbsp;"></optgroup>', '', $forumlist);
	}
	return $forumlist;
}

function visitedforums() {
	global $_G;

	$count = 0;
	$visitedforums = '';
	$fidarray = array($_G['forum']['fid']);
	$_G['cookie']['visitedfid'] = isset($_G['cookie']['visitedfid']) ? $_G['cookie']['visitedfid'] : '';

	if(!empty($_G['cookie']['visitedfid'])) {
		foreach(explode('D', $_G['cookie']['visitedfid']) as $fid) {
			if(isset($_G['cache']['forums'][$fid]) && !in_array($fid, $fidarray)) {
				if($fid != $_G['forum']['fid']) {
					$visitedforums .= '<li><a href="forum.php?mod=forumdisplay&fid='.$fid.'">'.$_G['cache']['forums'][$fid]['name'].'</a></li>';
					if(++$count >= $_G['setting']['visitedforums']) {
						break;
					}
				}
				$fidarray[] = $fid;
			}
		}
	}
	if(($visitedfid = implode('D', $fidarray)) != $_G['cookie']['visitedfid']) {
		dsetcookie('visitedfid', $visitedfid, 2592000);
	}
	return $visitedforums;
}

function moddisplay($moderators, $type, $inherit = 0) {
	if($moderators) {
		$modlist = $comma = '';
		foreach(explode("\t", $moderators) as $moderator) {
			$modlist .= $comma.'<a href="home.php?mod=space&username='.rawurlencode($moderator).'" class="notabs" c="1">'.($inherit ? '<strong>'.$moderator.'</strong>' : $moderator).'</a>';
			$comma = ', ';
		}
	} else {
		$modlist = '';
	}
	return $modlist;
}

function getcacheinfo($tid) {
	global $_G;
	$tid = intval($tid);
	$cachethreaddir2 = DISCUZ_ROOT.'./'.$_G['setting']['cachethreaddir'];
	$cache = array('filemtime' => 0, 'filename' => '');
	$tidmd5 = substr(md5($tid), 3);
	$fulldir = $cachethreaddir2.'/'.$tidmd5[0].'/'.$tidmd5[1].'/'.$tidmd5[2].'/';
	$cache['filename'] = $fulldir.$tid.'.htm';
	if(file_exists($cache['filename'])) {
		$cache['filemtime'] = filemtime($cache['filename']);
	} else {
		if(!is_dir($fulldir)) {
			dmkdir($fulldir);
		}
	}
	return $cache;
}

function replace_formhash($timestamp, $input) {
	global $_G;
	$temp_md5 = md5(substr($timestamp, 0, -3).substr($_G['config']['security']['authkey'], 3, -3));
	$temp_formhash = substr($temp_md5, 8, 8);
	$input = preg_replace('/(name=[\'|\"]formhash[\'|\"] value=[\'\"]|formhash=)'.$temp_formhash.'/ismU', '${1}'.constant("FORMHASH"), $input);
	//避免siteurl伪造被缓存
	$temp_siteurl = 'siteurl_'.substr($temp_md5, 16, 8);
	$input = preg_replace('/("|\')'.$temp_siteurl.'/ismU', '${1}'.$_G['siteurl'], $input);
	return $input;
}

function recommendupdate($fid, &$modrecommend, $force = '', $position = 0) {
	global $_G;

	$recommendlist = $modedtids = array();
	$num = $modrecommend['num'] ? intval($modrecommend['num']) : 10;
	$imagenum = $modrecommend['imagenum'] = $modrecommend['imagenum'] ? intval($modrecommend['imagenum']) : 0;
	$imgw = $modrecommend['imagewidth'] = $modrecommend['imagewidth'] ? intval($modrecommend['imagewidth']) : 200;
	$imgh = $modrecommend['imageheight'] = $modrecommend['imageheight'] ? intval($modrecommend['imageheight']) : 150;

	if($modrecommend['sort'] && (TIMESTAMP - $modrecommend['updatetime'] > $modrecommend['cachelife'] || $force)) {
		foreach(C::t('forum_forumrecommend')->fetch_all_by_fid($fid) as $row) {
			if($modrecommend['sort'] == 2 && $row['moderatorid']) {
				$modedtids[] = $row['tid'];
			}
		}
		C::t('forum_forumrecommend')->delete_by_fid($fid, $modrecommend['sort'] == 2 ? 0 : false);
		$orderby = 'dateline';

		$dateline = $modrecommend['dateline'] ? (TIMESTAMP - $modrecommend['dateline'] * 3600) : null;
		$recommends = null;
		switch($modrecommend['orderby']) {
			case '':
			case '1':$orderby = 'lastpost';break;
			case '2':$orderby = 'views';break;
			case '3':$orderby = 'replies';break;
			case '4':$orderby = 'digest';break;
			case '5':$orderby = 'recommends';$recommends = 0;break;
			case '6':$orderby = 'heats';break;
		}

		$i = 0;
		$addthread = $addimg = $recommendlist = $recommendimagelist = $tids = array();
		foreach(C::t('forum_thread')->fetch_all_by_fid_displayorder($fid, 0, $dateline, $recommends, 0, $num, $orderby) as $thread) {
			$recommendlist[$thread['tid']] = $thread;
			$tids[] = $thread['tid'];
			if(!$modedtids || !in_array($thread['tid'], $modedtids)) {
				$addthread[$thread['tid']] = array(
					'fid' => $thread['fid'],
					'tid' => $thread['tid'],
					'position' => 1,
					'displayorder' => $i,
					'subject' => $thread['subject'],
					'author' => $thread['author'],
					'authorid' => $thread['authorid'],
					'moderatorid' => 0,
					'expiration' => 0,
					'highlight' => $thread['highlight']
				);
				$i++;
			}
		}
		if($tids && $imagenum) {
			$attachtables = array();
			foreach($tids as $tid) {
				$attachtables[getattachtablebytid($tid)][] = $tid;
			}
			foreach($attachtables as $attachtable => $tids) {
				$attachmentpost = array();
				$postlist = C::t('forum_post')->fetch_all_by_tid(0, $tids, false, '', 0, 0, 1);
				if($postlist) {
					$pids = array();
					foreach($postlist as $post) {
						$pids[] = $post['pid'];
					}
					$attachmentlist = C::t('forum_attachment_n')->fetch_all_by_pid_width('tid:'.$tids[0], $pids, $imgw);
					if($attachmentlist) {
						foreach($attachmentlist as $k => $attachment) {
							$attachmentpost[$k]['fid'] = $postlist[$attachment['pid']]['fid'];
							$attachmentpost[$k]['tid'] = $postlist[$attachment['pid']]['tid'];
							$attachmentpost[$k]['aid'] = $attachment['aid'];
						}
					}
					unset($postlist, $attachmentlist, $pids);
				}
				foreach($attachmentpost as $attachment) {
					if(isset($recommendimagelist[$attachment['tid']])) {
						continue;
					}
					$key = md5($attachment['aid'].'|'.$imgw.'|'.$imgh);
					$recommendlist[$attachment['tid']]['filename'] = $attachment['aid']."\t".$imgw."\t".$imgh."\t".$key;
					$recommendimagelist[$attachment['tid']] = $recommendlist[$attachment['tid']];
					$recommendimagelist[$attachment['tid']]['subject'] = addslashes($recommendimagelist[$attachment['tid']]['subject']);
					$addthread[$attachment['tid']]['aid'] = '';
					$addthread[$attachment['tid']]['filename'] = $recommendlist[$attachment['tid']]['filename'];
					$addthread[$attachment['tid']]['typeid'] = 1;
					if(count($recommendimagelist) == $imagenum) {
						break;
					}
				}
			}
		}
		unset($recommendimagelist);

		if($addthread) {
			foreach($addthread as $row) {
				C::t('forum_forumrecommend')->insert($row, false, true);
			}
			$modrecommend['updatetime'] = TIMESTAMP;
			$modrecommendnew = serialize($modrecommend);
			C::t('forum_forumfield')->update($fid, array('modrecommend' => $modrecommendnew));
		}
	}

	$recommendlists = $recommendlist = $recommendimagelist = array();
	foreach(C::t('forum_forumrecommend')->fetch_all_by_fid($fid, $position) as $recommend) {
		if(($recommend['expiration'] && $recommend['expiration'] > TIMESTAMP) || !$recommend['expiration']) {
			if($recommend['filename'] && strexists($recommend['filename'], "\t")) {
				$imgd = explode("\t", $recommend['filename']);
				if($imgd[0] && $imgd[3]) {
					$recommend['filename'] = getforumimg($imgd[0], 0, $imgd[1], $imgd[2]);
				}
			}
			$recommendlist[] = $recommend;
			if($recommend['typeid'] && count($recommendimagelist) < $imagenum) {
				$recommendimagelist[] = $recommend;
			}
		}
		if(count($recommendlist) == $num) {
			break;
		}
	}

	if($recommendlist) {
		$_G['forum_colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');
		foreach($recommendlist as $thread) {
			if($thread['highlight']) {
				$string = sprintf('%02d', $thread['highlight']);
				$stylestr = sprintf('%03b', $string[0]);

				$thread['highlight'] = ' style="';
				$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
				$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
				$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
				$thread['highlight'] .= $string[1] ? 'color: '.$_G['forum_colorarray'][$string[1]] : '';
				$thread['highlight'] .= '"';
			} else {
				$thread['highlight'] = '';
			}
			$recommendlists[$thread['tid']]['author'] = $thread['author'];
			$recommendlists[$thread['tid']]['authorid'] = $thread['authorid'];
			$recommendlists[$thread['tid']]['subject'] = $modrecommend['maxlength'] ? cutstr($thread['subject'], $modrecommend['maxlength']) : $thread['subject'];
			$recommendlists[$thread['tid']]['subjectstyles'] = $thread['highlight'];
		}
	}

	if($recommendimagelist && $recommendlist) {
		$recommendlists['images'] = $recommendimagelist;
	}

	return $recommendlists;
}

function showstars($num) {
	global $_G;
	$return = '';
	$alt = 'title="Rank: '.$num.'"';
	if(empty($_G['setting']['starthreshold'])) {
		for($i = 0; $i < $num; $i++) {
			$return .= '<i class="fico-star1 fic4 fc-l" '.$alt.'></i>';
		}
	} else {
		for($i = 3; $i > 0; $i--) {
			$numlevel = intval($num / pow($_G['setting']['starthreshold'], ($i - 1)));
			$num = ($num % pow($_G['setting']['starthreshold'], ($i - 1)));
			for($j = 0; $j < $numlevel; $j++) {
				$return .= '<i class="fico-star'.$i.' fic4 fc-l" '.$alt.'></i>';
			}
		}
	}
	return $return;
}

function get_forumimg($imgname) {
	global $_G;
	if($imgname) {
		$parse = parse_url($imgname);
		if(isset($parse['host'])) {
			$imgpath = $imgname;
		} else {
			if($_G['forum']['status'] != 3) {
				$imgpath = $_G['setting']['attachurl'].'common/'.$imgname;
			} else {
				$imgpath = $_G['setting']['attachurl'].'group/'.$imgname;
			}
		}
		return $imgpath;
	}
}

function forumleftside() {
	global $_G;
	$leftside = array('favorites' => array(), 'forums' => array());
	$leftside['forums'] = forumselect(FALSE, 1);
	if($_G['uid']) {
		foreach(C::t('home_favorite')->fetch_all_by_uid_idtype($_G['uid'], 'fid') as $id => $forum) {
			if($_G['fid'] == $forum['id']) {
				$_G['forum_fidinfav'] = $forum['favid'];
			}
			$leftside['favorites'][$forum['id']] = array($forum['title'], $forum['favid']);
		}
	}
	$_G['leftsidewidth_mwidth'] = $_G['setting']['leftsidewidth'] + 15;
	return $leftside;
}

function threadclasscount($fid, $id = 0, $idtype = '', $count = null) {
	if(!$fid) {
		return false;
	}
	$typeflag = ($id && $idtype && in_array($idtype, array('typeid', 'sortid')));
	$threadclasscount = C::t('common_cache')->fetch('threadclasscount_'.$fid);
	$threadclasscount = dunserialize($threadclasscount['cachevalue']);
	if($count !== null) {
		if($typeflag) {
			$threadclasscount[$idtype][$id] = $count;
			C::t('common_cache')->insert(array(
				'cachekey' => 'threadclasscount_'.$fid,
				'cachevalue' => serialize($threadclasscount),
			), false, true);
			return true;
		} else {
			return false;
		}
	} else {
		if($typeflag) {
			return $threadclasscount[$idtype][$id];
		} else {
			return $threadclasscount;
		}
	}

}

function get_attach($list, $video = false, $audio = false){
	global $_G;
	require_once libfile('function/post');
	require_once libfile('function/discuzcode');
	$tids = $threads = $attachtableid_array = $threadlist_data = $posttableids = array();
	foreach($list as $value) {
		$tids[] = $value['tid'];
		if(!in_array($value['posttableid'], $posttableids)){
			$posttableids[] = $value['posttableid'];
		}
		$threads[$value['tid']] = $value;
	}
	foreach ($posttableids as $id) {
		$posts = C::t('forum_post')->fetch_all_by_tid($id, $tids, true, '', 0, 0, 1, null, null, null);
		foreach($posts as $value) {
			if(!$_G['forum']['ismoderator'] && $value['status'] & 1) {
				$threadlist_data[$value['tid']]['message'] = lang('forum/template', 'message_single_banned');
			} elseif(strpos($value['message'], '[/password]') !== FALSE) {
				$threadlist_data[$value['tid']]['message'] = lang('forum/template', 'message_password_exists');
			} elseif($threads[$value['tid']]['readperm'] > 0) {
				$value['message'] = '';
			} else {
				if($threads[$value['tid']]['price'] > 0) {
					preg_match_all("/\[free\](.+?)\[\/free\]/is", $value['message'], $matches);
					$value['message'] = '';
					if(!empty($matches[1])) {
						foreach($matches[1] as $match) {
							$value['message'] .= $match.' ';
						}
					}
				}
				if($value['message'] && ($video || $audio)){
					$value['media'] = '';
					$value['message'] = preg_replace(array("/\[hide=?\d*\](.*?)\[\/hide\]/is"), array(""), $value['message']);
					$value['msglower'] = strtolower($value['message']);
					if(strpos($value['msglower'], '[/media]') !== FALSE && $video) {
						preg_match("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", $value['message'], $value['video']);
						$threadlist_data[$value['tid']]['media'] = parsemedia($value['video'][1], $value['video'][2]);
					}elseif(strpos($value['msglower'], '[/audio]') !== FALSE && $audio) {
						preg_match("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", $value['message'], $value['audio']);
						$threadlist_data[$value['tid']]['media'] = parseaudio($value['audio'][2], 400);
					}
				}
				$threadlist_data[$value['tid']]['message'] = messagecutstr($value['message'], 90);
				if($threads[$value['tid']]['attachment'] == 2) {
					$attachtableid_array[getattachtableid($value['tid'])][] = $value['pid'];
				}
			}
		}
	}
	foreach($attachtableid_array as $tableid => $pids) {
		$attachs = C::t('forum_attachment_n')->fetch_all_by_pid_width($tableid, $pids, 0);
		foreach($attachs as $value){
			$threadlist_data[$value['tid']]['attachment'][] = getforumimg($value['aid'], 0, 2000, 550);
		}
	}
	return $threadlist_data;
}
?>