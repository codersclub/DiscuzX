<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: user.php 1174 2014-11-03 04:38:12Z hypowang $
*/

!defined('IN_UC') && exit('Access Denied');

define('UC_USER_CHECK_USERNAME_FAILED', -1);
define('UC_USER_USERNAME_BADWORD', -2);
define('UC_USER_USERNAME_EXISTS', -3);
define('UC_USER_EMAIL_FORMAT_ILLEGAL', -4);
define('UC_USER_EMAIL_ACCESS_ILLEGAL', -5);
define('UC_USER_EMAIL_EXISTS', -6);
define('UC_USER_USERNAME_CHANGE_FAILED', -7);
define('UC_USER_SECMOBILE_EXISTS', -8);

class usercontrol extends base {


	function __construct() {
		$this->usercontrol();
	}

	function usercontrol() {
		parent::__construct();
		$this->load('user');
		$this->app = $this->cache['apps'][UC_APPID];
	}

	function onsynlogin() {
		$this->init_input();
		$uid = $this->input('uid');
		if($this->app['synlogin']) {
			if($this->user = $_ENV['user']->get_user_by_uid($uid)) {
				$synstr = '';
				foreach($this->cache['apps'] as $appid => $app) {
					if($app['synlogin'] && $app['appid'] != $this->app['appid']) {
						$synstr .= '<script type="text/javascript" src="'.$app['url'].'/api/uc.php?time='.$this->time.'&code='.urlencode($this->authcode('action=synlogin&username='.$this->user['username'].'&uid='.$this->user['uid'].'&password='.$this->user['password']."&time=".$this->time, 'ENCODE', $app['authkey'])).'"></script>';
					}
				}
				return $synstr;
			}
		}
		return '';
	}

	function onsynlogout() {
		$this->init_input();
		if($this->app['synlogin']) {
			$synstr = '';
			foreach($this->cache['apps'] as $appid => $app) {
				if($app['synlogin'] && $app['appid'] != $this->app['appid']) {
					$synstr .= '<script type="text/javascript" src="'.$app['url'].'/api/uc.php?time='.$this->time.'&code='.urlencode($this->authcode('action=synlogout&time='.$this->time, 'ENCODE', $app['authkey'])).'"></script>';
				}
			}
			return $synstr;
		}
		return '';
	}

	function onregister() {
		$this->init_input();
		$username = $this->input('username');
		$password =  $this->input('password');
		$email = $this->input('email');
		$questionid = $this->input('questionid');
		$answer = $this->input('answer');
		$regip = $this->input('regip');
		$secmobicc = $this->input('secmobicc');
		$secmobile = $this->input('secmobile');

		if(($status = $this->_check_username($username)) < 0) {
			return $status;
		}
		if(($status = $this->_check_email($email)) < 0) {
			return $status;
		}
		if(($status = $this->_check_secmobile($secmobicc, $secmobile)) > 0) {
			return -8;
		}

		$uid = $_ENV['user']->add_user($username, $password, $email, 0, $questionid, $answer, $regip, $secmobicc, $secmobile);
		return $uid;
	}

	function onedit() {
		$this->init_input();
		$username = $this->input('username');
		$oldpw = $this->input('oldpw');
		$newpw = $this->input('newpw');
		$email = $this->input('email');
		$ignoreoldpw = $this->input('ignoreoldpw');
		$questionid = $this->input('questionid');
		$answer = $this->input('answer');
		$secmobicc = $this->input('secmobicc');
		$secmobile = $this->input('secmobile');

		if(!$ignoreoldpw && $email && ($status = $this->_check_email($email, $username)) < 0) {
			return $status;
		}
		if(($status = $this->_check_secmobile($secmobicc, $secmobile)) > 0) {
			return -8;
		}

		$status = $_ENV['user']->edit_user($username, $oldpw, $newpw, $email, $ignoreoldpw, $questionid, $answer, $secmobicc, $secmobile);

		if($newpw && $status > 0) {
			$this->load('note');
			$_ENV['note']->add('updatepw', 'username='.urlencode($username).'&password=');
			$_ENV['note']->send();
		}
		return $status;
	}

	function onlogin() {
		$this->init_input();
		$isuid = $this->input('isuid');
		$username = $this->input('username');
		$password = $this->input('password');
		$checkques = $this->input('checkques');
		$questionid = $this->input('questionid');
		$answer = $this->input('answer');
		$ip = $this->input('ip');
		$nolog = $this->input('nolog');

		// check_times 代表允许用户登录失败次数，该变量的值为 0 为不限制，正数为次数
		// 由于历史 Bug ，系统配置内原有用于代表无限制的 0 值必须代表正常值 5 ，因此只能在这里进行映射，负数映射为 0 ，正数正常， 0 映射为 5 。
		$check_times = $this->settings['login_failedtime'] > 0 ? $this->settings['login_failedtime'] : ($this->settings['login_failedtime'] < 0 ? 0 : 5);

		if($ip && $check_times && !$loginperm = $_ENV['user']->can_do_login($username, $ip)) {
			$status = -4;
			return array($status, '', $password, '', 0);
		}

		if($isuid == 1) {
			$user = $_ENV['user']->get_user_by_uid($username);
		} elseif($isuid == 2) {
			$user = $_ENV['user']->get_user_by_email($username);
		} elseif($isuid == 4) {
			// isuid == 4 则为手机号码登录，isuid == 3 已被应用占用
			list($secmobicc, $secmobile) = explode('-', $username);
			$user = $_ENV['user']->get_user_by_secmobile($secmobicc, $secmobile);
		} else {
			$user = $_ENV['user']->get_user_by_username($username);
		}

		if(empty($user)) {
			$status = -1;
		} elseif(!$_ENV['user']->verify_password($password, $user['password'], $user['salt'])) {
			$status = -2;
		} elseif($checkques && $user['secques'] != $_ENV['user']->quescrypt($questionid, $answer)) {
			$status = -3;
		} else {
			// 密码升级作为附属流程, 失败与否不影响登录操作
			$_ENV['user']->upgrade_password($username, $password, $user['password'], $user['salt']);
			$status = $user['uid'];
		}
		if(!$nolog && $ip && $check_times && $status <= 0) {
			$_ENV['user']->loginfailed($username, $ip);
		}
		$merge = $status != -1 && !$isuid && $_ENV['user']->check_mergeuser($username) ? 1 : 0;
		return array($status, $user['username'], $password, $user['email'], $merge);
	}

	function onlogincheck() {
		$this->init_input();
		$username = $this->input('username');
		$ip = $this->input('ip');
		return $_ENV['user']->can_do_login($username, $ip);
	}

	function oncheck_email() {
		$this->init_input();
		$email = $this->input('email');
		return $this->_check_email($email);
	}

	function oncheck_secmobile() {
		$this->init_input();
		$secmobicc = $this->input('secmobicc');
		$secmobile = $this->input('secmobile');
		return $this->_check_secmobile($secmobicc, $secmobile);
	}

	function oncheck_username() {
		$this->init_input();
		$username = $this->input('username');
		if(($status = $this->_check_username($username)) < 0) {
			return $status;
		} else {
			return 1;
		}
	}

	function onget_user() {
		$this->init_input();
		$username = $this->input('username');
		if(!$this->input('isuid')) {
			$status = $_ENV['user']->get_user_by_username($username);
		} else {
			$status = $_ENV['user']->get_user_by_uid($username);
		}
		if($status) {
			return array($status['uid'],$status['username'],$status['email']);
		} else {
			return 0;
		}
	}

	function onchgusername() {
		$this->init_input();
		$uid = $this->input('uid');
		$newusername = $this->input('newusername');
		if(($status = $this->_check_username($newusername)) < 0) {
			return $status;
		}
		$user = $_ENV['user']->get_user_by_uid($uid);
		$oldusername = $user['username'];
		if($_ENV['user']->chgusername($uid, $newusername)) {
			$_ENV['user']->user_log($uid, 'renameuser', 'uid='.$uid.'&oldusername='.urlencode($oldusername).'&newusername='.urlencode($newusername));
			$this->load('note');
			$_ENV['note']->add('renameuser', 'uid='.$uid.'&oldusername='.urlencode($oldusername).'&newusername='.urlencode($newusername));
			$_ENV['note']->send();
			return 1;
		}
		return UC_USER_USERNAME_CHANGE_FAILED;
	}

	function ongetprotected() {
		$this->init_input();
		$protectedmembers = $this->db->fetch_all("SELECT uid,username FROM ".UC_DBTABLEPRE."protectedmembers GROUP BY username");
		return $protectedmembers;
	}

	function ondelete() {
		$this->init_input();
		$uid = $this->input('uid');
		return $_ENV['user']->delete_user($uid);
	}

	function onaddprotected() {
		$this->init_input();
		$username = $this->input('username');
		$admin = $this->input('admin');
		$appid = $this->app['appid'];
		$usernames = (array)$username;
		foreach($usernames as $username) {
			$user = $_ENV['user']->get_user_by_username($username);
			$uid = $user['uid'];
			$this->db->query("REPLACE INTO ".UC_DBTABLEPRE."protectedmembers SET uid='$uid', username='$username', appid='$appid', dateline='{$this->time}', admin='$admin'", 'SILENT');
		}
		return $this->db->errno() ? -1 : 1;
	}

	function ondeleteprotected() {
		$this->init_input();
		$username = $this->input('username');
		$appid = $this->app['appid'];
		$usernames = (array)$username;
		foreach($usernames as $username) {
			$this->db->query("DELETE FROM ".UC_DBTABLEPRE."protectedmembers WHERE username='$username' AND appid='$appid'");
		}
		return $this->db->errno() ? -1 : 1;
	}

	function onmerge() {
		$this->init_input();
		$oldusername = $this->input('oldusername');
		$newusername = $this->input('newusername');
		$uid = $this->input('uid');
		$password = $this->input('password');
		$email = $this->input('email');
		if(($status = $this->_check_username($newusername)) < 0) {
			return $status;
		}
		$uid = $_ENV['user']->add_user($newusername, $password, $email, $uid);
		$this->db->query("DELETE FROM ".UC_DBTABLEPRE."mergemembers WHERE appid='".$this->app['appid']."' AND username='$oldusername'");
		return $uid;
	}

	function onmerge_remove() {
		$this->init_input();
		$username = $this->input('username');
		$this->db->query("DELETE FROM ".UC_DBTABLEPRE."mergemembers WHERE appid='".$this->app['appid']."' AND username='$username'");
		return NULL;
	}

	function _check_username($username) {
		$username = addslashes(trim(stripslashes($username)));
		if(!$_ENV['user']->check_username($username)) {
			return UC_USER_CHECK_USERNAME_FAILED;
		} elseif(!$_ENV['user']->check_usernamecensor($username)) {
			return UC_USER_USERNAME_BADWORD;
		} elseif($_ENV['user']->check_usernameexists($username)) {
			return UC_USER_USERNAME_EXISTS;
		}
		return 1;
	}

	function _check_email($email, $username = '') {
		if(empty($this->settings)) {
			$this->settings = $this->cache('settings');
		}
		if(!$_ENV['user']->check_emailformat($email)) {
			return UC_USER_EMAIL_FORMAT_ILLEGAL;
		} elseif(!$_ENV['user']->check_emailaccess($email)) {
			return UC_USER_EMAIL_ACCESS_ILLEGAL;
		} elseif(!$this->settings['doublee'] && $_ENV['user']->check_emailexists($email, $username)) {
			return UC_USER_EMAIL_EXISTS;
		} else {
			return 1;
		}
	}

	function _check_secmobile($secmobicc, $secmobile, $username = '') {
		return $_ENV['user']->check_secmobileexists($secmobicc, $secmobile, $username);
	}

	function onuploadavatar() {
	}

	function onrectavatar() {
	}
	function flashdata_decode($s) {
	}
}

?>