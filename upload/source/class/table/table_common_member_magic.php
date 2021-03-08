<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_member_magic.php 27757 2012-02-14 03:08:15Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_member_magic extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_member_magic';
		$this->_pk    = '';

		parent::__construct();
	}

	public function delete($val = null, $unbuffered = false) {
		// $val = null 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered);
		} else {
			$unbuffered = $unbuffered === false ? null : $unbuffered;
			return $this->delete_magic($val, $unbuffered);
		}
	}

	public function delete_magic($uid = null, $magicid = null) {
		// $uid = null, $magicid = null 需要在取消兼容层后删除
		$para = array();
		if($uid) {
			$para[] = DB::field('uid', $uid);
		}
		if($magicid) {
			$para[] = DB::field('magicid', $magicid);
		}
		if(!($where = $para ? implode(' AND ', $para) : '')) {
			return null;
		}
		return DB::delete($this->_table, $where);
	}

	public function fetch_all($ids, $force_from_db = false, $null1 = 0, $null2 = 0) {
		// $null 1~n 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			$force_from_db = $force_from_db === false ? '' : $force_from_db;
			return $this->fetch_all_magic($ids, $force_from_db, $null1, $null2);
		}
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_magic($id, $force_from_db);
		}
	}

	public function count($null1 = null, $null2 = null) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::count();
		} else {
			if ($null1 === null || $null2 === null) {
				throw new Exception("Invalid Use C:t('common_member_magic')->count Function.");
			}
			return $this->count_magic($null1, $null2);
		}
	}

	public function fetch_all_magic($uid, $magicid = '', $start = 0, $limit = 0) {
		$para = array();
		if($uid) {
			$para[] = DB::field('uid', $uid);
		}
		if($magicid) {
			$para[] = DB::field('magicid', $magicid);
		}
		if($limit) {
			$sql = DB::limit($start, $limit);
		}
		if(!count($para)) {
			return null;
		}
		$para = implode(' AND ', $para);
		return DB::fetch_all('SELECT * FROM %t WHERE %i', array($this->_table, $para.$sql));
	}

	public function fetch_magic($uid, $magicid) {
		$para = array();
		if($uid) {
			$para[] = DB::field('uid', $uid);
		}
		if($magicid) {
			$para[] = DB::field('magicid', $magicid);
		}
		if(!count($para)) {
			return null;
		}
		$sql = implode(' AND ', $para);
		return DB::fetch_first('SELECT * FROM %t WHERE %i', array($this->_table, $sql));
	}

	public function count_magic($uid, $magicid) {
		$para = array();
		if($uid) {
			$para[] = DB::field('uid', $uid);
		}
		if($magicid) {
			$para[] = DB::field('magicid', $magicid);
		}
		if(!count($para)) {
			return null;
		}
		$sql = implode(' AND ', $para);
		return (int) DB::result_first('SELECT count(*) FROM %t WHERE %i', array($this->_table, $sql));
	}

	public function increase($uid, $magicid, $setarr, $slient = false, $unbuffered = false) {
		$para = array();
		$setsql = array();
		$allowkey = array('num');
		foreach($setarr as $key => $value) {
			if(($value = intval($value)) && in_array($key, $allowkey)) {
				$setsql[] = "`$key`=`$key`+'$value'";
			}
		}
		if($uid) {
			$para[] = DB::field('uid', $uid);
		}
		if($magicid) {
			$para[] = DB::field('magicid', $magicid);
		}
		if(!count($para) || !count($setsql)) {
			return null;
		}
		$sql = implode(' AND ', $para);
		return DB::query('UPDATE %t SET %i WHERE %i', array($this->_table, implode(',', $setsql), $sql), $slient, $unbuffered);
	}

	public function count_by_uid($uid) {
		return DB::result_first('SELECT COUNT(*) FROM %t mm, %t m WHERE mm.uid=%d AND mm.magicid=m.magicid', array($this->_table, 'common_magic', $uid));
	}

	public function fetch_magicid_by_identifier($uid, $identifier) {
		return DB::result_first('SELECT m.magicid FROM %t mm,%t m WHERE mm.uid=%d AND m.identifier=%s AND mm.magicid=m.magicid', array($this->_table, 'common_magic', $uid, $identifier));
	}

}

?>