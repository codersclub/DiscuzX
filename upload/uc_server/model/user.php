<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: user.php 1179 2014-11-03 07:11:25Z hypowang $
*/

!defined('IN_UC') && exit('Access Denied');

class usermodel {

	var $db;
	var $base;
	var $passwordsetting;

	function __construct(&$base) {
		$this->usermodel($base);
	}

	function usermodel(&$base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	function get_user_by_uid($uid) {
		$arr = $this->db->fetch_first("SELECT * FROM ".UC_DBTABLEPRE."members WHERE uid='$uid'");
		return $arr;
	}

	function get_user_by_username($username) {
		$arr = $this->db->fetch_first("SELECT * FROM ".UC_DBTABLEPRE."members WHERE username='$username'");
		return $arr;
	}

	function get_user_by_email($email) {
		$arr = $this->db->fetch_first("SELECT * FROM ".UC_DBTABLEPRE."members WHERE email='$email'");
		return $arr;
	}

	function get_user_by_secmobile($secmobicc, $secmobile) {
		return $this->db->fetch_first_stmt("SELECT * FROM ".UC_DBTABLEPRE."members WHERE secmobicc=? AND secmobile=?", array('d', 'd'), array($secmobicc, $secmobile));
	}

	function check_username($username) {
		$charset = strtolower(UC_CHARSET);
		if ($charset === 'utf-8') {
			// \xE3\x80\x80: utf-8 全角空格
			// \xE6\xB8\xB8\xE5\xAE\xA2: utf-8 游客
			// \xE9\x81\x8A\xE5\xAE\xA2: utf-8 遊客
			$guestexp = '\xE3\x80\x80|\xE6\xB8\xB8\xE5\xAE\xA2|\xE9\x81\x8A\xE5\xAE\xA2';
		} elseif ($charset === 'gbk') {
			// \xA1\xA1: GBK 全角空格
			// \xD3\xCE\xBF\xCD: GBK 游客
			$guestexp = '\xA1\xA1|\xD3\xCE\xBF\xCD';
		} elseif ($charset === 'big5') {
			// \xA1\x40: BIG5 全角空格
			// \xB9\x43\xAB\xC8: BIG5 遊客
			$guestexp = '\xA1\x40|\xB9\x43\xAB\xC8';
		} else {
			return FALSE;
		}
		$guestexp .= '|^Guest';

		$len = $this->dstrlen($username);
		if($len > 15 || $len < 3 || preg_match("/\s+|^c:\\con\\con|[%,\*\"\s\<\>\&\(\)']|$guestexp/is", $username)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function dstrlen($str) {
		if(strtolower(UC_CHARSET) != 'utf-8') {
			return strlen($str);
		}
		$count = 0;
		for($i = 0; $i < strlen($str); $i++){
			$value = ord($str[$i]);
			if($value > 127) {
				$count++;
				if($value >= 192 && $value <= 223) $i++;
				elseif($value >= 224 && $value <= 239) $i = $i + 2;
				elseif($value >= 240 && $value <= 247) $i = $i + 3;
		    	}
	    		$count++;
		}
		return $count;
	}

	function check_mergeuser($username) {
		$data = $this->db->result_first("SELECT count(*) FROM ".UC_DBTABLEPRE."mergemembers WHERE appid='".$this->base->app['appid']."' AND username='$username'");
		return $data;
	}

	function check_usernamecensor($username) {
		$_CACHE['badwords'] = $this->base->cache('badwords');
		$censorusername = $this->base->get_setting('censorusername');
		$censorusername = $censorusername['censorusername'];
		$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($censorusername = trim($censorusername)), '/')).')$/i';
		$usernamereplaced = isset($_CACHE['badwords']['findpattern']) && !empty($_CACHE['badwords']['findpattern']) ? @preg_replace($_CACHE['badwords']['findpattern'], $_CACHE['badwords']['replace'], $username) : $username;
		if(($usernamereplaced != $username) || ($censorusername && preg_match($censorexp, $username))) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function check_usernameexists($username) {
		$data = $this->db->result_first("SELECT username FROM ".UC_DBTABLEPRE."members WHERE username='$username'");
		return $data;
	}

	function check_emailformat($email) {
		return strlen($email) > 6 && strlen($email) <= 255 && preg_match("/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/", $email);
	}

	function check_emailaccess($email) {
		$setting = $this->base->get_setting(array('accessemail', 'censoremail'));
		$accessemail = $setting['accessemail'];
		$censoremail = $setting['censoremail'];
		$accessexp = '/('.str_replace("\r\n", '|', preg_quote(trim($accessemail), '/')).')$/i';
		$censorexp = '/('.str_replace("\r\n", '|', preg_quote(trim($censoremail), '/')).')$/i';
		if($accessemail || $censoremail) {
			if(($accessemail && !preg_match($accessexp, $email)) || ($censoremail && preg_match($censorexp, $email))) {
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			return TRUE;
		}
	}

	function check_emailexists($email, $username = '') {
		$sqladd = $username !== '' ? "AND username<>'$username'" : '';
		$email = $this->db->result_first("SELECT email FROM  ".UC_DBTABLEPRE."members WHERE email='$email' $sqladd");
		return $email;
	}

	function check_secmobileexists($secmobicc, $secmobile, $username = '') {
		$sqladd = $username !== '' ? "AND username<>'$username'" : '';
		$secmobicc == 0 && $secmobicc = '';
		$secmobile == 0 && $secmobile = '';
		$secmobile = $this->db->result_first("SELECT secmobile FROM  ".UC_DBTABLEPRE."members WHERE secmobicc='$secmobicc' AND secmobile='$secmobile' $sqladd");
		return $secmobile;
	}

	function check_login($username, $password, &$user) {
		$user = $this->get_user_by_username($username);
		if(empty($user['username'])) {
			return -1;
		} elseif(!$this->verify_password($password, $user['password'], $user['salt'])) {
			return -2;
		}
		// 密码升级作为附属流程, 失败与否不影响登录操作
		$this->upgrade_password($username, $password, $user['password'], $user['salt']);
		return $user['uid'];
	}

	function add_user($username, $password, $email, $uid = 0, $questionid = '', $answer = '', $regip = '', $secmobicc = '', $secmobile = '') {
		$regip = empty($regip) ? $this->base->onlineip : $regip;
		$salt = '';
		$password = $this->generate_password($password);
		$sqladd = $uid ? "uid='".intval($uid)."'," : '';
		$sqladd .= $questionid > 0 ? " secques='".$this->quescrypt($questionid, $answer)."'," : " secques='',";
		$sqladd .= $secmobicc ? "secmobicc='".$secmobicc."'," : '';
		$sqladd .= $secmobile ? "secmobile='".$secmobile."'," : '';
		$this->db->query("INSERT INTO ".UC_DBTABLEPRE."members SET $sqladd username='$username', password='$password', email='$email', regip='$regip', regdate='".$this->base->time."', salt='$salt'");
		$uid = $this->db->insert_id();
		$this->db->query("INSERT INTO ".UC_DBTABLEPRE."memberfields SET uid='$uid'");
		return $uid;
	}

	function edit_user($username, $oldpw, $newpw, $email, $ignoreoldpw = 0, $questionid = '', $answer = '', $secmobicc = '', $secmobile = '') {
		$data = $this->db->fetch_first("SELECT username, uid, password, salt FROM ".UC_DBTABLEPRE."members WHERE username='$username'");

		if($ignoreoldpw) {
			$isprotected = $this->db->result_first("SELECT COUNT(*) FROM ".UC_DBTABLEPRE."protectedmembers WHERE uid = '{$data['uid']}'");
			if($isprotected) {
				return -8;
			}
		}

		if(!$ignoreoldpw && !$this->verify_password($oldpw, $data['password'], $data['salt'])) {
			return -1;
		}

		$sqladd = $newpw ? "password='".$this->generate_password($newpw)."', salt=''" : '';
		$sqladd .= $email ? ($sqladd ? ',' : '')." email='$email'" : '';
		//空字符串代表没传递这个参数，传递0时，代表清空这个数据
		$sqladd .= $secmobicc !== '' ? ($sqladd ? ',' : '').(!empty($secmobicc) ? " secmobicc='$secmobicc'" : " secmobicc=''") : '';
		$sqladd .= $secmobile !== '' ? ($sqladd ? ',' : '').(!empty($secmobile) ? " secmobile='$secmobile'" : " secmobile=''") : '';
		if($questionid !== '') {
			if($questionid > 0) {
				$sqladd .= ($sqladd ? ',' : '')." secques='".$this->quescrypt($questionid, $answer)."'";
			} else {
				$sqladd .= ($sqladd ? ',' : '')." secques=''";
			}
		}
		if($sqladd || $emailadd) {
			$this->db->query("UPDATE ".UC_DBTABLEPRE."members SET $sqladd WHERE username='$username'");
			return $this->db->affected_rows();
		} else {
			return -7;
		}
	}

	function delete_user($uidsarr) {
		$uidsarr = (array)$uidsarr;
		if(!$uidsarr) {
			return 0;
		}
		$uids = $this->base->implode($uidsarr);
		$arr = $this->db->fetch_all("SELECT uid FROM ".UC_DBTABLEPRE."protectedmembers WHERE uid IN ($uids)");
		$puids = array();
		foreach((array)$arr as $member) {
			$puids[] = $member['uid'];
		}
		$uids = $this->base->implode(array_diff($uidsarr, $puids));
		if($uids) {
			$this->db->query("DELETE FROM ".UC_DBTABLEPRE."members WHERE uid IN($uids)");
			$this->db->query("DELETE FROM ".UC_DBTABLEPRE."memberfields WHERE uid IN($uids)");
			$this->delete_useravatar($uidsarr);
			$this->base->load('note');
			$_ENV['note']->add('deleteuser', "ids=$uids");
			return $this->db->affected_rows();
		} else {
			return 0;
		}
	}

	function delete_useravatar($uidsarr) {
		if(!defined('UC_DELAVTDIR')) {
			define('UC_DELAVTDIR', UC_DATADIR.'./avatar/');
		}
		$uidsarr = (array)$uidsarr;
		foreach((array)$uidsarr as $uid) {
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'big', 'real')) && unlink($avatar_file);
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'middle', 'real')) && unlink($avatar_file);
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'small', 'real')) && unlink($avatar_file);
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'big')) && unlink($avatar_file);
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'middle')) && unlink($avatar_file);
			file_exists($avatar_file = UC_DELAVTDIR.$this->base->get_avatar($uid, 'small')) && unlink($avatar_file);
		}
	}

	function chgusername($uid, $newusername) {
		return $this->db->query_stmt("UPDATE ".UC_DBTABLEPRE."members SET username=? WHERE uid=?", array('s', 'i'), array($newusername, $uid));
	}

	function get_total_num($sqladd = '') {
		$data = $this->db->result_first("SELECT COUNT(*) FROM ".UC_DBTABLEPRE."members $sqladd");
		return $data;
	}

	function get_list($page, $ppp, $totalnum, $sqladd) {
		$start = $this->base->page_get_start($page, $ppp, $totalnum);
		$data = $this->db->fetch_all("SELECT * FROM ".UC_DBTABLEPRE."members $sqladd LIMIT $start, $ppp");
		return $data;
	}

	function name2id($usernamesarr) {
		$usernamesarr = daddslashes($usernamesarr, 1, TRUE);
		$usernames = $this->base->implode($usernamesarr);
		$query = $this->db->query("SELECT uid FROM ".UC_DBTABLEPRE."members WHERE username IN($usernames)");
		$arr = array();
		while($user = $this->db->fetch_array($query)) {
			$arr[] = $user['uid'];
		}
		return $arr;
	}

	function id2name($uidarr) {
		$arr = array();
		$query = $this->db->query("SELECT uid, username FROM ".UC_DBTABLEPRE."members WHERE uid IN (".$this->base->implode($uidarr).")");
		while($user = $this->db->fetch_array($query)) {
			$arr[$user['uid']] = $user['username'];
		}
		return $arr;
	}

	function quescrypt($questionid, $answer) {
		return $questionid > 0 && $answer != '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
	}

	function can_do_login($username, $ip = '') {

		// check_times 代表允许用户登录失败次数，该变量的值为 0 为不限制，正数为次数
		// 由于历史 Bug ，系统配置内原有用于代表无限制的 0 值必须代表正常值 5 ，因此只能在这里进行映射，负数映射为 0 ，正数正常， 0 映射为 5 。
		$check_times = $this->base->settings['login_failedtime'] > 0 ? $this->base->settings['login_failedtime'] : ($this->base->settings['login_failedtime'] < 0 ? 0 : 5);

		if($check_times == 0) {
			return -1;
		}

		$username = substr(md5($username), 8, 15);
		$expire = 15 * 60;
		if(!$ip) {
			$ip = $this->base->onlineip;
		}

		$ip_check = $user_check = array();
		$query = $this->db->query("SELECT * FROM ".UC_DBTABLEPRE."failedlogins WHERE ip='".$ip."' OR ip='$username'");
		while($row = $this->db->fetch_array($query)) {
			if($row['ip'] === $username) {
				$user_check = $row;
			} elseif($row['ip'] === $ip) {
				$ip_check = $row;
			}
		}

		if(empty($ip_check) || ($this->base->time - $ip_check['lastupdate'] > $expire)) {
			$ip_check = array();
			$this->db->query("REPLACE INTO ".UC_DBTABLEPRE."failedlogins (ip, count, lastupdate) VALUES ('{$ip}', '0', '{$this->base->time}')");
		}

		if(empty($user_check) || ($this->base->time - $user_check['lastupdate'] > $expire)) {
			$user_check = array();
			$this->db->query("REPLACE INTO ".UC_DBTABLEPRE."failedlogins (ip, count, lastupdate) VALUES ('{$username}', '0', '{$this->base->time}')");
		}

		if ($ip_check || $user_check) {
			$time_left = min(($check_times - (isset($ip_check['count']) ? $ip_check['count'] : 0)), ($check_times - (isset($user_check['count']) ? $user_check['count'] : 0)));
			return $time_left;

		}

		$this->db->query("DELETE FROM ".UC_DBTABLEPRE."failedlogins WHERE lastupdate<".($this->base->time - ($expire + 1)), 'UNBUFFERED');

		return $check_times;
	}

	function loginfailed($username, $ip = '') {
		$username = substr(md5($username), 8, 15);
		if(!$ip) {
			$ip = $this->base->onlineip;
		}
		$this->db->query("UPDATE ".UC_DBTABLEPRE."failedlogins SET count=count+1, lastupdate='".$this->base->time."' WHERE ip='".$ip."' OR ip='$username'");
	}

	function user_log($uid, $action, $extra = '') {
		$uid = intval($uid);
		$action = addslashes($action);
		$extra = addslashes($extra);
		$this->db->query_stmt("INSERT INTO ".UC_DBTABLEPRE."memberlogs SET uid=?, action=?, extra=?", array('i', 's', 's'), array($uid, $action, $extra));
	}

	function user_log_total_num() {
		return $this->db->result_first("SELECT COUNT(*) FROM ".UC_DBTABLEPRE."memberlogs");
	}

	function user_log_list($page, $ppp, $totalnum) {
		$start = $this->base->page_get_start($page, $ppp, $totalnum);
		return $this->db->fetch_all("SELECT * FROM ".UC_DBTABLEPRE."memberlogs LIMIT $start, $ppp");
	}

	function get_passwordalgo() {
		$algo = $this->base->settings['passwordalgo'];
		if(empty($algo)) {
			return constant('PASSWORD_BCRYPT');
		} else {
			return constant($algo) === null ? constant('PASSWORD_BCRYPT') : constant($algo);
		}
	}

	function get_passwordoptions() {
		$options = $this->base->settings['passwordoptions'];
		if(empty($options)) {
			return array();
		} else {
			$result = json_decode($options, true);
			return is_array($result) ? $result : array();
		}
	}

	function generate_password($password) {
		$algo = $this->get_passwordalgo();
		$options = $this->get_passwordoptions();
		// 当用户配置有问题时, password_hash 可能返回 false 或无法校验通过的密码, 此时使用 BCRYPT 备份方案生成密码, 保证上游应用正常
		// 密码散列算法会在部分出错情况下返回 NULL 并报 Warning, 在此特殊处理
		$hash = password_hash($password, $algo, $options);
		return ($hash === false || $hash === null || !password_verify($password, $hash)) ? password_hash($password, PASSWORD_BCRYPT) : $hash;
	}

	function verify_password($password, $hash, $salt = '') {
		// salt 为空说明是新算法, 直接根据 password_verify 出结果
		// 否则如 strlen(salt) == 6 说明是老算法, 适用老算法匹配
		// 均不符合则为第三方转换算法, 如符合命名规则则根据 salt 匹配第三方文件
		if(empty($salt)) {
			return password_verify($password, $hash);
		} else if(strlen($salt) == 6) {
			return hash_equals($hash, md5(md5($password).$salt));
		} else if(strlen($salt) > 6 && strlen($salt) < 20 && file_exists(UC_ROOT . "lib/uc_password_$salt.class.php")) {
			$classname = "uc_password_$salt";
			include(UC_ROOT . "lib/uc_password_$salt.class.php");
			return $classname::verify_password($password, $hash);
		}
		return false;
	}

	function upgrade_password($username, $password, $hash, $salt = '') {
		$algo = $this->get_passwordalgo();
		$options = $this->get_passwordoptions();
		if (!empty($salt) || password_needs_rehash($hash, $algo, $options)) {
			$password_new = $this->generate_password($password);
			$sqladd = "password = '$password_new', salt = ''";
			return $this->db->query("UPDATE ".UC_DBTABLEPRE."members SET $sqladd WHERE username='$username'");
		}
		return true;
	}

	function reset_founderpw($newpw, $reconfkey = 1) {
		$configfile = UC_ROOT.'./data/config.inc.php';
		if(!is_writable($configfile)) {
			return -4;
		} else {
			$config = file_get_contents($configfile);
			$salt = '';
			$hashnewpw = str_replace('$', '#', $this->generate_password($newpw));
			$config = preg_replace("/define\('UC_FOUNDERSALT',\s*'.*?'\);/i", "define('UC_FOUNDERSALT', '$salt');", $config);
			$config = preg_replace("/define\('UC_FOUNDERPW',\s*'.*?'\);/i", "define('UC_FOUNDERPW', '$hashnewpw');", $config);
			if($reconfkey) {
				$uckey = $this->base->generate_key(64);
				$config = preg_replace("/define\('UC_KEY',\s*'.*?'\);/i", "define('UC_KEY', '$uckey');", $config);
			}
			$config = str_replace('#', '$', $config);
			if(file_put_contents($configfile, $config) === false) {
				return -4;
			}
			return 2;
		}
	}

	function upgrade_founderpw($password, $hash, $salt = '') {
		$algo = $this->get_passwordalgo();
		$options = $this->get_passwordoptions();
		if (!empty($salt) || password_needs_rehash($hash, $algo, $options)) {
			$password_new = $this->generate_password($password);
			return $this->reset_founderpw($password);
		}
		return true;
	}

}