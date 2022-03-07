<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: admin.php 1139 2012-05-08 09:02:11Z liulanbo $
*/

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	function __construct() {
		$this->control();
	}

	function control() {
		parent::__construct();
		$this->load('user');
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadminbadword']) {
			$this->message('no_permission_for_this_module');
		}
	}

	function onls() {

		$status = 0;
		if(!empty($_POST['addname']) && $this->submitcheck()) {
			$addname = getgpc('addname', 'P');
			$this->view->assign('addname', $addname);
			$uid = $this->db->result_first("SELECT uid FROM ".UC_DBTABLEPRE."members WHERE username='$addname'");
			if($uid) {
				$adminuid = $this->db->result_first("SELECT uid FROM ".UC_DBTABLEPRE."admins WHERE username='$addname'");
				if($adminuid) {
					$status = -1;
				} else {
					$allowadminsetting = getgpc('allowadminsetting', 'P');
					$allowadminapp = getgpc('allowadminapp', 'P');
					$allowadminuser = getgpc('allowadminuser', 'P');
					$allowadminbadword = getgpc('allowadminbadword', 'P');
					$allowadmincredits = getgpc('allowadmincredits', 'P');
					$allowadmintag = getgpc('allowadmintag', 'P');
					$allowadminpm = getgpc('allowadminpm', 'P');
					$allowadmindomain = getgpc('allowadmindomain', 'P');
					$allowadmindb = getgpc('allowadmindb', 'P');
					$allowadminnote = getgpc('allowadminnote', 'P');
					$allowadmincache = getgpc('allowadmincache', 'P');
					$allowadminlog = getgpc('allowadminlog', 'P');
					$this->db->query("INSERT INTO ".UC_DBTABLEPRE."admins SET
						uid='$uid',
						username='$addname',
						allowadminsetting='$allowadminsetting',
						allowadminapp='$allowadminapp',
						allowadminuser='$allowadminuser',
						allowadminbadword='$allowadminbadword',
						allowadmincredits='$allowadmincredits',
						allowadmintag='$allowadmintag',
						allowadminpm='$allowadminpm',
						allowadmindomain='$allowadmindomain',
						allowadmindb='$allowadmindb',
						allowadminnote='$allowadminnote',
						allowadmincache='$allowadmincache',
						allowadminlog='$allowadminlog'");
					$insertid = $this->db->insert_id();
					if($insertid) {
						$this->writelog('admin_add', 'username='.dhtmlspecialchars($addname));
						$status = 1;
					} else {
						$status = -2;
					}
				}
			} else {
				$status = -3;
			}
		}

		if(!empty($_POST['editpwsubmit']) && $this->submitcheck()) {
			$oldpw = getgpc('oldpw', 'P');
			$newpw = getgpc('newpw', 'P');
			$newpw2 = getgpc('newpw2', 'P');
			$reconfkey = getgpc('reconfkey', 'P');
			if($_ENV['user']->verify_password($oldpw, UC_FOUNDERPW, UC_FOUNDERSALT) || hash_equals(UC_FOUNDERPW, md5(md5($oldpw).UC_FOUNDERSALT))) {
				if($newpw != $newpw2) {
					$status = -6;
				} else {
					$status = $_ENV['user']->reset_founderpw($newpw, $reconfkey);
					if($status === 2) {
						$this->writelog('admin_pw_edit');
					}
				}
			} else {
				$status = -5;
			}
		}

		$this->view->assign('status', $status);

		if(!empty($_POST['delete'])) {
			$uids = $this->implode(getgpc('delete', 'P'));
			$this->db->query("DELETE FROM ".UC_DBTABLEPRE."admins WHERE uid IN ($uids)");
		}

		$page = max(1, getgpc('page'));
		$ppp  = 15;
		$totalnum = $this->db->result_first("SELECT COUNT(*) FROM ".UC_DBTABLEPRE."admins");
		$start = $this->page_get_start($page, $ppp, $totalnum);
		$userlist = $this->db->fetch_all("SELECT a.*,m.* FROM ".UC_DBTABLEPRE."admins a LEFT JOIN ".UC_DBTABLEPRE."members m USING(uid) LIMIT $start, $ppp");
		$multipage = $this->page($totalnum, $ppp, $page, UC_ADMINSCRIPT.'?m=admin&a=admin');
		if($userlist) {
			foreach($userlist as $key => $user) {
				$user['regdate'] = $this->date($user['regdate']);
				$userlist[$key] = $user;
			}
		}

		$a = getgpc('a');
		$this->view->assign('a', $a);
		$this->view->assign('multipage', $multipage);
		$this->view->assign('userlist', $userlist);
		$this->view->display('admin_admin');

	}

	function onedit() {
		$uid = getgpc('uid');
		$status = 0;
		if($this->submitcheck()) {
			$allowadminsetting = getgpc('allowadminsetting', 'P');
			$allowadminapp = getgpc('allowadminapp', 'P');
			$allowadminuser = getgpc('allowadminuser', 'P');
			$allowadminbadword = getgpc('allowadminbadword', 'P');
			$allowadmintag = getgpc('allowadmintag', 'P');
			$allowadminpm = getgpc('allowadminpm', 'P');
			$allowadmincredits = getgpc('allowadmincredits', 'P');
			$allowadmindomain = getgpc('allowadmindomain', 'P');
			$allowadmindb = getgpc('allowadmindb', 'P');
			$allowadminnote = getgpc('allowadminnote', 'P');
			$allowadmincache = getgpc('allowadmincache', 'P');
			$allowadminlog = getgpc('allowadminlog', 'P');
			$this->db->query("UPDATE ".UC_DBTABLEPRE."admins SET
				allowadminsetting='$allowadminsetting',
				allowadminapp='$allowadminapp',
				allowadminuser='$allowadminuser',
				allowadminbadword='$allowadminbadword',
				allowadmincredits='$allowadmincredits',
				allowadmintag='$allowadmintag',
				allowadminpm='$allowadminpm',
				allowadmindomain='$allowadmindomain',
				allowadmindb='$allowadmindb',
				allowadminnote='$allowadminnote',
				allowadmincache='$allowadmincache',
				allowadminlog='$allowadminlog'
				WHERE uid='$uid'");
			$status = $this->db->errno() ? -1 : 1;
			$this->writelog('admin_priv_edit', 'username='.dhtmlspecialchars($admin));
		}
		$admin = $this->db->fetch_first("SELECT * FROM ".UC_DBTABLEPRE."admins WHERE uid='$uid'");
		$this->view->assign('uid', $uid);
		$this->view->assign('admin', $admin);
		$this->view->assign('status', $status);
		$this->view->display('admin_admin');
	}

}

?>