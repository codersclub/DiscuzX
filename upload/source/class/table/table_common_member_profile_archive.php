<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_member_profile_archive.php 28589 2012-03-05 09:54:11Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_member_profile_archive extends table_common_member_profile
{
	protected $_fields;

	public function __construct() {

		parent::__construct();
		$this->_table = 'common_member_profile_archive';
		$this->_pk    = 'uid';
	}

	public function fetch($id, $force_from_db = false, $fetch_archive = 0) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db, $fetch_archive);
		} else {
			return $this->fetch_profile($id);
		}
	}

	public function fetch_all($ids, $force_from_db = false, $fetch_archive = 1) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db, $fetch_archive);
		} else {
			return $this->fetch_all_profile($ids);
		}
	}

	public function delete($val, $unbuffered = false, $fetch_archive = 0) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered, $fetch_archive);
		} else {
			return $this->delete_profile($val, $unbuffered);
		}
	}

	public function fetch_profile($id) {
		return ($id = dintval($id)) ? DB::fetch_first('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $id)) : array();
	}

	public function fetch_all_profile($ids) {
		$data = array();
		if(($ids = dintval($ids, true))) {
			$query = DB::query('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $ids));
			while($value = DB::fetch($query)) {
				$data[$value[$this->_pk]] = $value;
			}
		}
		return $data;
	}

	public function delete_profile($val, $unbuffered = false) {
		return ($val = dintval($val, true)) && DB::delete($this->_table, DB::field($this->_pk, $val), null, $unbuffered);
	}
}

?>