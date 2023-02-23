<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id$
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function searchindex_cache() {
	global $_G;
	include_once DISCUZ_ROOT.'./source/discuz_version.php';
	if(preg_match("#\d{8}#i", DISCUZ_RELEASE)) {
		$cachedata = "lang('admincp_searchindex');\n\$searchindex = & \$_G['lang']['admincp_searchindex'];";
		require_once libfile('function/cache');
		writetocache('searchindex', $cachedata);
		return null;
	}

	$siteurl = $_G['siteurl'];
	$_G['siteurl'] = '';
	require DISCUZ_ROOT.'./source/language/lang_admincp_menu.php';
	$menulang = $lang;
	require DISCUZ_ROOT.'./source/language/lang_admincp.php';
	$genlang = $lang + $menulang;
	$_G['siteurl'] = $siteurl;
	$indexdata = array();

	require DISCUZ_ROOT.'./source/admincp/admincp_menu.php';
	foreach($menu as $topmenu => $leftmenu) {
		foreach($leftmenu as $item) {
			if(!isset($item[2]) && isset($menulang[$item[0]])) {
				list($action, $operation, $do) = explode('_', $item[1]);
				$indexdata[] = array('index' => array(
					$menulang[$item[0]] => 'action='.$action.($operation ? '&operation='.$operation.($do ? '&do='.$do : '') : '')
				), 'text' => array($menulang[$item[0]]));
			}
		}
	}

	$genlangi = '|'.implode('|', array_keys($genlang)).'|';

	$flag = false;
	$dir = opendir(DISCUZ_ROOT.'./source/admincp/');
	while($entry = readdir($dir)) {
		if($entry != '.' && $entry != '..' && preg_match('/^admincp\_/', $entry)) {

			$adminfile = DISCUZ_ROOT.'./source/admincp/'.$entry;
			$data = file_get_contents($adminfile);
			$data = preg_replace('/\/\/.+?\r/', '', $data);
			$data = preg_replace_callback(
				'/\/\*(.+?)\*\//s',
				function ($matches) {
					if(!preg_match('/^search/i', $matches[1])) {
						return '';
					} else {
						return '/*'.$matches[1].'*/';
					}
				},
				$data
			);
			$isfullindex = preg_match_all('#/\*search=\s*(\{.+?\})\s*\*/(.+?)/\*search\*/#is', $data, $search);
			if($isfullindex) {
				foreach($search[0] as $k => $item) {
					$search[1][$k] = stripslashes($search[1][$k]);
					$titles = json_decode($search[1][$k], 1);
					$titlesnew = $titletext = array();
					foreach($titles as $title => $url) {
						$titlekey = strip_tags(isset($genlang[$title]) ? $genlang[$title] : $title);
						$titlesnew[$titlekey] = $url;
						if($titlekey[0] != '_') {
							$titletext[] = $titlekey;
						}
					}
					$data = $search[2][$k];
					$l = $tm = array();
					preg_match_all("/(showsetting|showtitle|showtableheader|showtips)\('(\w+)'/", $data, $r);
					if($r[2]) {
						if($titletext) {
							$l[] = implode(' &raquo; ', $titletext);
						}
						foreach($r[2] as $i) {
							if(in_array($i,$tm)) {
								continue;
							}
							$tm[] = $i;
							$l[] = strip_tags($i);
							$l[] = strip_tags($genlang[$i]);
							$preg = '/\|('.preg_quote($i).'_comment)\|/';
							preg_match_all($preg, $genlangi, $lr);
							if($lr[1]) {
								foreach($lr[1] as $li) {
									$l[] = strip_tags($genlang[$li]);
								}
							}
						}
					}

					preg_match_all("/\\\$lang\['(\w+)'\]/", $data, $r);
					if($r[1]) {
						if(empty($l) && $titletext) {
							$l[] = implode(' &raquo; ', $titletext);
						}
						foreach($r[1] as $i) {
							if(in_array($i,$tm)) {
								continue;
							}
							$tm[] = $i;
							$l[] = strip_tags($i);
							$l[] = strip_tags($genlang[$i]);
							$preg = '/\|('.preg_quote($i).'_comment)\|/';
							preg_match_all($preg, $genlangi, $lr);
							if($lr[1]) {
								foreach($lr[1] as $li) {
									$l[] = strip_tags($genlang[$li]);
								}
							}
						}
					}
					if (!empty($l)) {
						$indexdata[] = array('index' => $titlesnew, 'text' => $l);
						$flag = true;
					}
				}
			}

		}
	}

	if($flag) {
		$cachedata = '$searchindex = '.var_export($indexdata, 1).';';
	} else {
		$cachedata = "lang('admincp_searchindex');\n\$searchindex = & \$_G['lang']['admincp_searchindex'];";
	}

	require_once libfile('function/cache');
	writetocache('searchindex', $cachedata);
}