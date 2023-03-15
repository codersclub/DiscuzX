<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_visit.php 30814 2012-06-21 06:37:56Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_visit extends discuz_table
{
	/*
	 * memory情况下，所有的数据保存于一个SortedSet下，member = ip, score = view
	 */
	public function __construct() {

		$this->_table = 'common_visit';
		$this->_pk    = 'ip';

		$this->_pre_cache_key = 'common_visit_';
		$this->_cache_ttl = -1;

		parent::__construct();
		// 依赖SortedSet数据类型
		$this->_allowmem = $this->_allowmem && C::memory()->gotsortedset;
	}

	public function inc($ip, $viewadd = 1) {
		if (!$this->_allowmem) {
			return DB::query('UPDATE %t SET view=view+(%d) WHERE `ip`=%s', array($this->_table, $viewadd, $ip));
		}
		return memory('zincrby', 'idx_ip_view', $ip, $viewadd, $this->_pre_cache_key);
	}

	public function range($start = 0, $limit = 0, $sort = '') {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::range($start, $limit, $sort);
		} else {
			return $this->range_visit($start, $limit);
		}
	}

	public function range_visit($start = 0, $limit = 0) {
		if (!$this->_allowmem) {
			return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).' ORDER BY view DESC'.DB::limit($start, $limit), $this->_pk);
		}
		list($ss, $ee) = $this->get_start_and_end($start, $limit);
		$rs = memory('zrevrangewithscore', 'idx_ip_view', $ss, $ee, $this->_pre_cache_key);
		$result = array();
		foreach ($rs as $ip => $view) {
			$result[] = array(
				'ip' => $ip,
				'view' => $view
			);
		}
		return $result;
	}

	public function delete($val, $unbuffered = false) {
		if (!$this->_allowmem) {
			return parent::delete($val, $unbuffered);
		}
		if (!is_array($val)) $val = array($val);
		foreach ($val as $ip) {
			memory('zrem', 'idx_ip_view', $ip, 0, $this->_pre_cache_key);
		}
		return TRUE;
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		if (!$this->_allowmem) {
			return parent::insert($data, $return_insert_id, $replace, $silent);
		}
		return memory('zadd', 'idx_ip_view', $data['ip'], $data['view'], $this->_pre_cache_key);
	}

	public function fetch($id, $force_from_db = false) {
		if (!$this->_allowmem) {
			return parent::fetch($id, $force_from_db);
		}
		$rs = memory('zscore', 'idx_ip_view', $id, 0, $this->_pre_cache_key);
		if ($rs) {
			return array(
				'ip' => $id,
				'view' => $rs
			);
		}
		return FALSE;
	}

	public function count() {
		if (!$this->_allowmem) {
			return parent::count();
		}
		return memory('zcard', 'idx_ip_view', $this->_pre_cache_key);
	}

	/*
	 * 从$start和$limit计算start和end
	 * 当$limit为0时，$start参数表示limit
	 */
	private function get_start_and_end($start, $limit) {
		$limit = intval($limit > 0 ? $limit : 0);
		$start = intval($start > 0 ? $start : 0);
		if ($start > 0 && $limit > 0) {
			return array($start, $start + $limit - 1);
		} elseif ($limit > 0) {
			return array(0, $limit - 1);
		} elseif ($start > 0) {
			return array(0, $start - 1);
		} else {
			return array(0, -1);
		}
	}
}

?>