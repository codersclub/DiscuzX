<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_admincp_session.php 27803 2012-02-15 02:39:36Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_admincp_session extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_admincp_session';
		$this->_pk    = 'uid';

		parent::__construct();
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_session($id, $force_from_db);
		}
	}

	public function delete($val, $unbuffered = false, $null = 3600) {
		// $null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered);
		} else {
			return $this->delete_session($val, $unbuffered, $null);
		}
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::update($val, $data, $unbuffered, $low_priority);
		} else {
			return $this->update_session($val, $data, $unbuffered);
		}
	}

	public function fetch_session($uid, $panel) {
		$sql = 'SELECT * FROM %t WHERE uid=%d AND panel=%d';
		return DB::fetch_first($sql, array($this->_table, $uid, $panel));
	}

	public function fetch_all_by_panel($panel) {
		return DB::fetch_all('SELECT * FROM %t WHERE panel=%d', array($this->_table, $panel), 'uid');
	}

	public function delete_session($uid, $panel, $ttl = 3600) {
		$sql = 'DELETE FROM %t WHERE (uid=%d AND panel=%d) OR dateline<%d';
		DB::query($sql, array($this->_table, $uid, $panel, TIMESTAMP-intval($ttl)));
	}

	public function update_session($uid, $panel, $data) {
		if(!empty($data) && is_array($data)) {
			DB::update($this->_table, $data, array('uid'=>$uid, 'panel'=>$panel));
		}
	}

}

?>