<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_forum_forum_threadtable.php 27819 2012-02-15 05:12:23Z svn_project_zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_forum_threadtable extends discuz_table
{
	public function __construct() {

		$this->_table = 'forum_forum_threadtable';
		$this->_pk    = '';

		parent::__construct();
	}

	public function count_by_fid($fids) {
		if(empty($fids)) {
			return 0;
		}
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE '.DB::field('fid', $fids), array($this->_table));
	}

	public function fetch_all_by_fid($fids) {
		if(empty($fids)) {
			return array();
		}
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('fid', $fids), array($this->_table));
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false, $null = false) {
		// $null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::update($val, $data, $unbuffered, $low_priority);
		} else {
			return $this->update_threadtable($val, $data, $unbuffered, $low_priority, $null);
		}
	}

	public function update_threadtable($fid, $threadtableid, $data, $unbuffered = false, $low_priority = false) {
		if(empty($data)) {
			return false;
		}
		return DB::update($this->_table, $data, array('fid' => $fid, 'threadtableid' => $threadtableid), $unbuffered, $low_priority);
	}

	public function update_by_threadtableid($threadtableid, $data, $unbuffered = false, $low_priority = false) {
		if(empty($data)) {
			return false;
		}
		return DB::update($this->_table, $data, DB::field('threadtableid', $threadtableid), $unbuffered, $low_priority);
	}

	public function delete($val, $unbuffered = false, $null = false) {
		// $null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered);
		} else {
			return $this->delete_threadtable($val, $unbuffered, $null);
		}
	}

	public function delete_threadtable($fid, $threadtableid, $unbuffered = false) {
		return DB::delete($this->_table, array('fid' => dintval($fid), 'threadtableid' => dintval($threadtableid)), null, $unbuffered);
	}

	public function delete_none_threads() {
		return DB::delete($this->_table, "threads='0'");
	}
}

?>