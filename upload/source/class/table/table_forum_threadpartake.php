<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_threadpartake.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_threadpartake extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_threadpartake';
		$this->_pk    = '';

		parent::__construct();
	}

	public function delete($val, $unbuffered = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered);
		} else {
			return $this->delete_threadpartake($val);
		}
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_threadpartake($id, $force_from_db);
		}
	}

	public function delete_threadpartake($dateline) {
		return DB::query('DELETE FROM %t WHERE dateline<%d', array($this->_table, $dateline), false, true);
	}

	public function fetch_threadpartake($tid, $uid) {
		return DB::fetch_first('SELECT * FROM %t WHERE tid=%d AND uid=%d', array($this->_table, $tid, $uid));
	}

}

?>