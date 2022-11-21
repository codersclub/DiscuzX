<?php

/*
	[NOTICE]
	This file is NOT part of UCenter!
	Developers should make their own extensions to handle UCenter notifications.
*/

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 */

(defined('IN_UC') || defined('IN_API')) or exit('Access denied');

if(!defined('API_RETURN_SUCCEED')) {
	define('API_RETURN_SUCCEED', '1');
	define('API_RETURN_FAILED', '-1');
	define('API_RETURN_FORBIDDEN', '-2');
}

class uc_note_handler {

	public static function deleteuser($get, $post) {
		global $_G;
		$uids = str_replace("'", '', stripslashes($get['ids']));
		$ids = array();
		$ids = array_keys(C::t('common_member')->fetch_all($uids));
		require_once DISCUZ_ROOT.'./source/function/function_delete.php';
		$ids && deletemember($ids);

		return API_RETURN_SUCCEED;
	}

	public static function renameuser($get, $post) {
		global $_G;
		$len = strlen($get['newusername']);
		if($len > 22 || $len < 3 || preg_match("/\s+|^c:\\con\\con|[%,\*\"\s\<\>\&\(\)']/is", $get['newusername'])) {
			return API_RETURN_FAILED;
		}

		$tables = array(
			'common_block' => array('id' => 'uid', 'name' => 'username'),
			'common_invite' => array('id' => 'fuid', 'name' => 'fusername'),
			'common_member_verify_info' => array('id' => 'uid', 'name' => 'username'),
			'common_mytask' => array('id' => 'uid', 'name' => 'username'),
			'common_report' => array('id' => 'uid', 'name' => 'username'),

			'forum_thread' => array('id' => 'authorid', 'name' => 'author'),
			'forum_activityapply' => array('id' => 'uid', 'name' => 'username'),
			'forum_groupuser' => array('id' => 'uid', 'name' => 'username'),
			'forum_pollvoter' => array('id' => 'uid', 'name' => 'username'),
			'forum_post' => array('id' => 'authorid', 'name' => 'author'),
			'forum_postcomment' => array('id' => 'authorid', 'name' => 'author'),
			'forum_ratelog' => array('id' => 'uid', 'name' => 'username'),

			'home_album' => array('id' => 'uid', 'name' => 'username'),
			'home_blog' => array('id' => 'uid', 'name' => 'username'),
			'home_clickuser' => array('id' => 'uid', 'name' => 'username'),
			'home_docomment' => array('id' => 'uid', 'name' => 'username'),
			'home_doing' => array('id' => 'uid', 'name' => 'username'),
			'home_feed' => array('id' => 'uid', 'name' => 'username'),
			'home_friend' => array('id' => 'fuid', 'name' => 'fusername'),
			'home_friend_request' => array('id' => 'fuid', 'name' => 'fusername'),
			'home_notification' => array('id' => 'authorid', 'name' => 'author'),
			'home_pic' => array('id' => 'uid', 'name' => 'username'),
			'home_poke' => array('id' => 'fromuid', 'name' => 'fromusername'),
			'home_share' => array('id' => 'uid', 'name' => 'username'),
			'home_show' => array('id' => 'uid', 'name' => 'username'),
			'home_specialuser' => array('id' => 'uid', 'name' => 'username'),
			'home_visitor' => array('id' => 'vuid', 'name' => 'vusername'),

			'portal_article_title' => array('id' => 'uid', 'name' => 'username'),
			'portal_comment' => array('id' => 'uid', 'name' => 'username'),
			'portal_topic' => array('id' => 'uid', 'name' => 'username'),
			'portal_topic_pic' => array('id' => 'uid', 'name' => 'username'),
		);

		if(!C::t('common_member')->update($get['uid'], array('username' => $get['newusername'])) && isset($_G['setting']['membersplit'])){
			C::t('common_member_archive')->update($get['uid'], array('username' => $get['newusername']));
		}

		loadcache("posttableids");
		if($_G['cache']['posttableids']) {
			$posttableids = is_array($_G['cache']['posttableids']) ? $_G['cache']['posttableids'] : array(0);
			foreach($posttableids AS $tableid) {
				$tables[getposttable($tableid)] = array('id' => 'authorid', 'name' => 'author');
			}
		}

		foreach($tables as $table => $conf) {
			DB::query("UPDATE ".DB::table($table)." SET `{$conf['name']}`='{$get['newusername']}' WHERE `{$conf['id']}`='{$get['uid']}'");
		}
		return API_RETURN_SUCCEED;
	}

	public static function updatepw($get, $post) {
		global $_G;
		$username = $get['username'];
		$newpw = md5(time().rand(100000, 999999));
		$uid = 0;
		if(($uid = C::t('common_member')->fetch_uid_by_username($username))) {
			$ext = '';
		} elseif(($uid = C::t('common_member_archive')->fetch_uid_by_username($username))) {
			$ext = '_archive';
		}
		if($uid) {
			C::t('common_member'.$ext)->update($uid, array('password' => $newpw));
		}

		return API_RETURN_SUCCEED;
	}

	public static function checkavatar($get, $post) {
		global $_G;
		$uid = $get['uid'];
		$size = $get['size'];
		$type = $get['type'];
		$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
		$uid = abs(intval($uid));
		$uid = sprintf("%09d", $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		$typeadd = $type == 'real' ? '_real' : '';
		if(!UC_AVTPATH) {
			$avtpath = './data/avatar/';
		} else {
			$avtpath = str_replace('..', '', UC_AVTPATH);
		}
		$avatarfile = realpath(DISCUZ_ROOT.$avtpath).'./'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
		if(file_exists($avatar_file)) {
			return API_RETURN_SUCCEED;
		} else {
			return API_RETURN_FAILED;
		}
	}

	public static function loadavatarpath() {
		global $_G;
		if(!defined('UC_DELAVTDIR')) {
			define('UC_DELAVTDIR', DISCUZ_ROOT.$_G['setting']['avatarpath'].'/');
		}
	}
}