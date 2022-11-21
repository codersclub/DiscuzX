<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_avatar.php 23850 2011-08-11 10:16:09Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['group']['allowavatarupload']) {
	showmessage('no_privilege_upload_avatar');
}

if(submitcheck('avatarsubmit')) {
	showmessage('do_success', 'cp.php?ac=avatar&quickforward=1');
}

loaducenter();
if(UC_STANDALONE) {
	define('UC_AVTAPI', $_G['siteurl'].'api/avatar');
}
$uc_avatarflash = uc_avatar($_G['uid'], 'virtual', 0);
$uc_avatarflash[] = 'standalone';
$uc_avatarflash[] = UC_STANDALONE;

if(empty($space['avatarstatus']) && uc_check_avatar($_G['uid'], 'middle')) {
	C::t('common_member')->update($_G['uid'], array('avatarstatus'=>'1'));

	updatecreditbyaction('setavatar');
}
$reload = intval($_GET['reload']);
$actives = array('avatar' =>' class="a"');
include template("home/spacecp_avatar");

?>