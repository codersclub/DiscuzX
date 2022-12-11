<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_portal.php 33047 2013-04-12 08:46:56Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function category_remake($catid) {
	global $_G;

	$cat = $_G['cache']['portalcategory'][$catid];
	if(empty($cat)) return array();
	require_once libfile('function/portalcp');
	$categoryperm = getallowcategory($_G['uid']);
	foreach ($_G['cache']['portalcategory'] as $value) {
		if($value['catid'] == $cat['upid']) {
			$cat['ups'][$value['catid']] = $value;
			$upid = $value['catid'];
			while(!empty($upid)) {
				if(!empty($_G['cache']['portalcategory'][$upid]['upid'])) {
					$upid = $_G['cache']['portalcategory'][$upid]['upid'];
					$cat['ups'][$upid] = $_G['cache']['portalcategory'][$upid];
				} else {
					$upid = 0;
				}
			}
		} elseif($value['upid'] == $cat['catid']) {
			$cat['subs'][$value['catid']] = $value;
		} elseif($value['upid'] == $cat['upid']) {
			if (!$value['closed'] || $_G['group']['allowdiy'] || $categoryperm[$value['catid']]['allowmanage']) {
				$cat['others'][$value['catid']] = $value;
			}
		}
	}
	if(!empty($cat['ups'])) $cat['ups'] = array_reverse($cat['ups'], TRUE);
	return $cat;
}

function getportalcategoryurl($catid) {
	if(empty($catid)) return '';
	loadcache('portalcategory');
	$portalcategory = getglobal('cache/portalcategory');
	if($portalcategory[$catid]) {
		return $portalcategory[$catid]['caturl'];
	} else {
		return '';
	}
}

function fetch_article_url($article) {
	global $_G;
	if(!empty($_G['setting']['makehtml']['flag']) && $article && $article['htmlmade']) {
		if(empty($_G['cache']['portalcategory'])) {
			loadcache('portalcategory');
		}
		$caturl = '';
		if(!empty($_G['cache']['portalcategory'][$article['catid']])) {
			$topid = $_G['cache']['portalcategory'][$article['catid']]['topid'];
			$caturl = $_G['cache']['portalcategory'][$topid]['domain'] ? $_G['cache']['portalcategory'][$topid]['caturl'] : '';
		}
		return $caturl.$article['htmldir'].$article['htmlname'].'.'.$_G['setting']['makehtml']['extendname'];
	} else {
		return 'portal.php?mod=view&aid='.$article['aid'];
	}
}

function fetch_topic_url($topic) {
	global $_G;
	if(!empty($_G['setting']['makehtml']['flag']) && $topic && $topic['htmlmade']) {
		return $_G['setting']['makehtml']['topichtmldir'].'/'.$topic['name'].'.'.$_G['setting']['makehtml']['extendname'];
	} else {
		return 'portal.php?mod=topic&topicid='.$topic['topicid'];
	}
}

function portal_get_list($page = 1, $perpage = 15, $wheresql = '') {
	global $_G;
	$page = min($page, 1000);
	$start = ($page-1) * $perpage;
	if($start < 0) {
		$start = 0;
	}
	$list = array();
	$pricount = 0;
	$multi = '';
	$count = C::t('portal_article_title')->fetch_all_by_sql($wheresql, '', 0, 0, 1, 'at');
	if($count) {
		$query = C::t('portal_article_title')->fetch_all_by_sql($wheresql, 'ORDER BY at.dateline DESC', $start, $perpage, 0, 'at');
		foreach($query as $value) {
			$value['catname'] = $_G['cache']['portalcategory'][$value['catid']]['catname'];
			$value['onerror'] = '';
			if($value['pic']) {
				$value['pic'] = pic_get($value['pic'], '', $value['thumb'], $value['remote'], 1, 1);
			}
			$value['dateline'] = dgmdate($value['dateline']);
			if($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1) {
				$list[] = $value;
			} else {
				$pricount++;
			}
		}
		$multi = multi($count, $perpage, $page, 'portal.php', 1000);
	}
	return $return = array('list'=>$list, 'count'=>$count, 'multi'=>$multi, 'pricount'=>$pricount);
}

function article_title_style($value = array()) {

	$style = array();
	$highlight = '';
	if($value['highlight']) {
		$style = explode('|', $value['highlight']);
		$highlight = ' style="';
		$highlight .= $style[0] ? 'color: '.$style[0].';' : '';
		$highlight .= $style[1] ? 'font-weight: bold;' : '';
		$highlight .= $style[2] ? 'font-style: italic;' : '';
		$highlight .= $style[3] ? 'text-decoration: underline;' : '';
		$highlight .= '"';
	}
	return $highlight;

}