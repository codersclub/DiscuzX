<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_threadprofile_group.php 31607 2012-09-13 08:38:40Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_threadprofile_group extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_threadprofile_group';
		$this->_pk    = 'gid';

		parent::__construct();
	}

	public function fetch_all($ids = null, $force_from_db = false) {
		// $ids = null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			return $this->fetch_all_threadprofile();
		}
	}

	public function fetch_all_threadprofile() {
		return DB::fetch_all('SELECT * FROM %t', array($this->table), $this->_pk);
	}

	public function delete_by_tpid($tpid) {
		DB::delete($this->table, "tpid='$tpid'");
	}

}

?>