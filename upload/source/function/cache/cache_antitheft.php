<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cache_antitheft.php 32740 2013-03-05 08:32:47Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_antitheft() {
	$data = C::t('common_setting')->fetch_setting('antitheftsetting', true);
	savecache('antitheft', $data);
}

?>