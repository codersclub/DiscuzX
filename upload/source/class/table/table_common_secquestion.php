<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_secquestion.php 27449 2012-02-01 05:32:35Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_secquestion extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_secquestion';
		$this->_pk    = 'id';

		parent::__construct();
	}

	public function fetch_all($ids = null, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			$ids = $ids === null ? 0 : $ids;
			$force_from_db = $force_from_db === false ? 0 : $force_from_db;
			return $this->fetch_all_secquestion($ids, $force_from_db);
		}
	}

	public function fetch_all_secquestion($start = 0, $limit = 0) {
		return DB::fetch_all('SELECT * FROM %t'.DB::limit($start, $limit), array($this->_table));
	}

	public function delete_by_type($type) {
		DB::query('DELETE FROM %t WHERE type=%d', array($this->_table, $type));
	}

}

?>