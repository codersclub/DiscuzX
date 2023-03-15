<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_banned.php 27876 2012-02-16 04:28:02Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_banned extends discuz_table
{
	/*
	 * 在memory启用的时候，存储于common_banned_index的SortedSet中
	 * member = ip的16进制表达, score = 1 表示封禁，score = 0 表示不封禁
	 */
	public function __construct() {

		$this->_table = 'common_banned';
		$this->_pk    = 'id';

		$this->_pre_cache_key = 'common_banned_';
		$this->_cache_ttl = 600;

		parent::__construct();

		$this->_allowmem = $this->_allowmem && C::memory()->gotsortedset;
	}

	public function fetch_by_ip($ip) {
		return DB::fetch_first('SELECT * FROM %t WHERE ip=%s', array($this->_table, $ip));
	}

	public function fetch_all_order_dateline() {
		return DB::fetch_all('SELECT * FROM %t ORDER BY dateline', array($this->_table));
	}

	public function fetch_all($ids = array(), $force_from_db = false) {
		// Todo: $ids = array() 需要在取消兼容层后删除
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			return $this->fetch_all_banned();
		}
	}

	public function fetch_all_banned() {
		return DB::fetch_all('SELECT * FROM %t', array($this->_table));
	}

	public function delete_by_id($ids, $adminid, $adminname) {
		$ids = array_map('intval', (array)$ids);
		if($ids) {
			if ($this->_allowmem) memory('rm', 'index', $this->_pre_cache_key);
			return DB::query('DELETE FROM %t WHERE id IN(%n) AND (1=%d OR `admin`=%s)', array($this->_table, $ids, $adminid, $adminname));
		}
		return 0;
	}

	public function update_expiration_by_id($id, $expiration, $isadmin, $admin) {
		if ($this->_allowmem) memory('rm', 'index', $this->_pre_cache_key);
		return DB::query('UPDATE %t SET expiration=%d WHERE id=%d AND (1=%d OR `admin`=%s)', array($this->_table, $expiration, $id, $isadmin, $admin));
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		$cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
		if (substr($data['lowerip'], 0, 2) !== '0x') $data['lowerip'] = '0x' . $data['lowerip'];
		if (substr($data['upperip'], 0, 2) !== '0x') $data['upperip'] = '0x' . $data['upperip'];
		if ($this->_allowmem) memory('rm', 'index', $this->_pre_cache_key);
		return DB::query(
			$cmd . ' %t SET `ip`=%s, `lowerip`=%i, `upperip`=%i, `admin`=%s, `dateline`=%d, `expiration`=%d',
			array($this->_table, $data['ip'], $data['lowerip'], $data['upperip'], $data['admin'], $data['dateline'], $data['expiration']),
			$silent, !$return_insert_id
		);
	}

	public function check_banned($time_to_check, $ip) {
		$iphex = ip::ip_to_hex_str($ip);
		$banned = true;
		if ($this->_allowmem) $banned = memory('zscore', 'index', $iphex, 0, $this->_pre_cache_key);
		if ($banned === false || !$this->_allowmem) { // 如果memory中没有值，或不使用memory，都走数据库
			$iphex_val = '0x' . $iphex;
			$ret = DB::result_first(
				"SELECT id from %t WHERE expiration > %d AND lowerip <= %i AND upperip >= %i",
				array($this->_table, $time_to_check, $iphex_val, $iphex_val)
			);
			if ($ret) {
				if ($this->_allowmem) memory('zadd', 'index', $iphex, 1, $this->_pre_cache_key);
				return true;
			}
			if ($this->_allowmem) memory('zadd', 'index', $iphex, 0, $this->_pre_cache_key);
			return false;
		}

		// _allowmem = true，并且 $banned 有值
		return $banned === 1;
	}

}

?>