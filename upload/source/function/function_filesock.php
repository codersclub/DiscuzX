<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_filesock.php 36279 2016-12-09 07:54:31Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function _dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype  = 'URLENCODE', $allowcurl = TRUE, $position = 0, $files = array()) {
	$param = array(
		'url' => $url,
		'limit' => $limit,
		'post' => $post,
		'cookie' => $cookie,
		'ip' => $ip,
		'block' => $block,
		'encodetype' => $encodetype,
		'allowcurl' => $allowcurl,
		'position' => $position,
		'files' => $files
	);
	$fs = filesock::open($param);
	return $fs->request();
}

?>