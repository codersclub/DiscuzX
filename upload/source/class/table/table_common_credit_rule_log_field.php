<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_credit_rule_log_field.php 27777 2012-02-14 07:07:26Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_credit_rule_log_field extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_credit_rule_log_field';
		$this->_pk    = '';

		parent::__construct();
	}

	public function delete_clid($val) {
		DB::delete($this->_table, DB::field('clid', $val));
	}

	public function delete_by_uid($uids) {
		return DB::delete($this->_table, DB::field('uid', $uids));
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::update($val, $data, $unbuffered, $low_priority);
		} else {
			return $this->update_field($val, $data, $unbuffered);
		}
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_field($id, $force_from_db);
		}
	}

	public function update_field($uid, $clid, $data) {
		if(!empty($data) && is_array($data)) {
			return DB::update($this->_table, $data, array('uid'=>$uid, 'clid'=>$clid));
		}
		return 0;
	}

	public function fetch_field($uid, $clid) {
		$logarr = array();
		if($uid && $clid) {
			$logarr = DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND clid=%d', array($this->_table, $uid, $clid));
		}
		return !empty($logarr) ? $logarr : array();
	}
}

?>