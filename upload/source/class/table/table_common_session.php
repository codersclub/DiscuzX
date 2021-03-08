<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_session.php 28051 2012-02-21 10:36:56Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_session extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_session';
		$this->_pk    = 'sid';

		parent::__construct();
	}

	public function fetch($id, $force_from_db = false, $null = false) {
		// $null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_session($id, $force_from_db, $null);
		}
	}

	public function fetch_session($sid, $ip = false, $uid = false) {
		if(empty($sid)) {
			return array();
		}
		$this->checkpk();
		$session = parent::fetch($sid);
		if($session && $ip !== false && $ip != "{$session['ip']}") {
			$session = array();
		}
		if($session && $uid !== false && $uid != $session['uid']) {
			$session = array();
		}
		return $session;
	}

	public function fetch_member($ismember = 0, $invisible = 0, $start = 0, $limit = 0) {
		$sql = array();
		if($ismember === 1) {
			$sql[] = 'uid > 0';
		} elseif($ismember === 2) {
			$sql[] = 'uid = 0';
		}
		if($invisible === 1) {
			$sql[] = 'invisible = 1';
		} elseif($invisible === 2) {
			$sql[] = 'invisible = 0';
		}
		$wheresql = !empty($sql) && is_array($sql) ? ' WHERE '.implode(' AND ', $sql) : '';
		$sql = 'SELECT * FROM %t '.$wheresql.' ORDER BY lastactivity DESC'.DB::limit($start, $limit);
		return DB::fetch_all($sql, array($this->_table), $this->_pk);
	}

	public function count_invisible($type = 1) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE invisible=%d', array($this->_table, $type));
	}

	public function count($type = 0) {
		$condition = $type == 1 ? ' WHERE uid>0 ' : ($type == 2 ? ' WHERE uid=0 ' : '');
		return DB::result_first("SELECT count(*) FROM ".DB::table($this->_table).$condition);

	}

	public function delete_by_session($session, $onlinehold, $guestspan) {
		if(empty($session) || !is_array($session)) return;
		$onlinehold = time() - $onlinehold;
		$guestspan = time() - $guestspan;

		$session = daddslashes($session);
		//当前用户的sid
		$condition = " sid='{$session['sid']}' ";
		//过期的 session
		$condition .= " OR lastactivity<$onlinehold ";
		//频繁的同一ip游客
		$condition .= " OR (uid='0' AND ".DB::field('ip', $session['ip'])." AND lastactivity>$guestspan) ";
		//当前用户的uid
		$condition .= $session['uid'] ? " OR (uid='{$session['uid']}') " : '';
		DB::delete('common_session', $condition);
	}

	public function fetch_by_uid($uid) {
		return !empty($uid) ? DB::fetch_first('SELECT * FROM %t WHERE uid=%d', array($this->_table, $uid)) : false;
	}

	public function fetch_all_by_uid($uids, $start = 0, $limit = 0) {
		$data = array();
		if(!empty($uids)) {
			$data = DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('uid', $uids).DB::limit($start, $limit), array($this->_table), null, 'uid');
		}
		return $data;
	}

	public function update_max_rows($max_rows) {
		return DB::query('ALTER TABLE '.DB::table('common_session').' MAX_ROWS='.dintval($max_rows));
	}

	public function clear() {
		return DB::query('DELETE FROM '.DB::table('common_session'));
	}

	public function count_by_fid($fid) {
		return ($fid = dintval($fid)) ? DB::result_first('SELECT COUNT(*) FROM '.DB::table('common_session')." WHERE uid>'0' AND fid='$fid' AND invisible='0'") : 0;
	}

	public function fetch_all_by_fid($fid, $limit = 12) {
		return ($fid = dintval($fid)) ? DB::fetch_all('SELECT uid, groupid, username, invisible, lastactivity FROM '.DB::table('common_session')." WHERE uid>'0' AND fid='$fid' AND invisible='0' ORDER BY lastactivity DESC".DB::limit($limit)) : array();
	}

	public function update_by_uid($uid, $data){
		if(($uid = dintval($uid)) && !empty($data) && is_array($data)) {
			return DB::update($this->_table, $data, DB::field('uid', $uid));
		}
		return 0;
	}

	public function count_by_ip($ip) {
		$count = 0;
		if(!empty($ip)) {
			$count = DB::result_first('SELECT COUNT(*) FROM '.DB::table('common_session')." WHERE ".DB::field('ip', $ip));
		}
		return $count;
	}

	public function fetch_all_by_ip($ip, $start = 0, $limit = 0) {
		$data = array();
		if(!empty($ip)) {
			$data = DB::fetch_all('SELECT * FROM %t WHERE ip=%s ORDER BY lastactivity DESC'.DB::limit($start, $limit), array($this->_table, $ip), null);
		}
		return $data;
	}
}

?>