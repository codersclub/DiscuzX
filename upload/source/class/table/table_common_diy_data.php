<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_diy_data.php 27827 2012-02-15 07:03:43Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_diy_data extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_diy_data';
		$this->_pk    = '';

		parent::__construct();
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_diy($id, $force_from_db);
		}
	}

	public function delete($val, $unbuffered = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::delete($val, $unbuffered);
		} else {
			return $this->delete_diy($val, $unbuffered);
		}
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::update($val, $data, $unbuffered, $low_priority);
		} else {
			return $this->update_diy($val, $data, $unbuffered);
		}
	}

	public function fetch_all($ids, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			$force_from_db = $force_from_db === false ? null : $force_from_db;
			return $this->fetch_all_diy($ids, $force_from_db);
		}
	}

	public function fetch_diy($targettplname, $tpldirectory) {
		return !empty($targettplname) ? DB::fetch_first('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field('targettplname', $targettplname).' AND '.DB::field('tpldirectory', $tpldirectory)) : array();
	}

	public function delete_diy($targettplname, $tpldirectory = null) {
		foreach($this->fetch_all_diy($targettplname, $tpldirectory) as $value) {
			$file = ($value['tpldirectory'] ? $value['tpldirectory'].'/' : '').$value['targettplname'];
			@unlink(DISCUZ_ROOT.'./data/diy/'.$file.'.htm');
			@unlink(DISCUZ_ROOT.'./data/diy/'.$file.'.htm.bak');
			@unlink(DISCUZ_ROOT.'./data/diy/'.$file.'_diy_preview.htm');
		}
		return DB::delete($this->_table, DB::field('targettplname', $targettplname).($tpldirectory !== null ? ' AND '.DB::field('tpldirectory', $tpldirectory) : ''));
	}

	public function update_diy($targettplname, $tpldirectory, $data) {
		if(!empty($targettplname) && !empty($data) && is_array($data)) {
			return DB::update($this->_table, $data, DB::field('targettplname', $targettplname).' AND '.DB::field('tpldirectory', $tpldirectory));
		}
		return false;
	}

	public function fetch_all_diy($targettplname, $tpldirectory = null) {
		return !empty($targettplname) ? DB::fetch_all('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field('targettplname', $targettplname).($tpldirectory !== null ? ' AND '.DB::field('tpldirectory', $tpldirectory) : '')) : array();
	}

	public function count_by_where($wheresql) {
		$wheresql = $wheresql ? ' WHERE '.$wheresql : '';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table).$wheresql);
	}

	public function fetch_all_by_where($wheresql, $ordersql, $start, $limit) {
		$wheresql = $wheresql ? ' WHERE '.$wheresql : '';
		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).$wheresql.' '.$ordersql.DB::limit($start, $limit), null, $this->_pk ? $this->_pk : '');
	}
}

?>