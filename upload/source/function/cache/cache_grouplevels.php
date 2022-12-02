<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cache_grouplevels.php 24623 2011-09-28 06:54:39Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_grouplevels() {
	$data = array();
	$query = C::t('forum_grouplevel')->range();
	foreach($query as $level) {
		$level['creditspolicy'] = dunserialize($level['creditspolicy']);
		$level['postpolicy'] = dunserialize($level['postpolicy']);
		$level['specialswitch'] = dunserialize($level['specialswitch']);
		$data[$level['levelid']] = $level;
	}

	savecache('grouplevels', $data);
}

?>