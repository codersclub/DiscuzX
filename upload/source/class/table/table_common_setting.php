<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_setting.php 30476 2012-05-30 07:05:06Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_setting extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_setting';
		$this->_pk    = 'skey';

		parent::__construct();
	}

	public function fetch($id, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch($id, $force_from_db);
		} else {
			return $this->fetch_setting($id, $force_from_db);
		}
	}

	public function fetch_all($ids, $force_from_db = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::fetch_all($ids, $force_from_db);
		} else {
			return $this->fetch_all_setting($ids, $force_from_db);
		}
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if (defined('DISCUZ_DEPRECATED')) {
			throw new Exception('NotImplementedException');
			return parent::update($val, $data, $unbuffered, $low_priority);
		} else {
			return $this->update_setting($val, $data);
		}
	}

	public function fetch_setting($skey, $auto_unserialize = false) {
		$data = DB::result_first('SELECT svalue FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $skey));
		return $auto_unserialize ? (array)dunserialize($data) : $data;
	}

	public function fetch_all_setting($skeys = array(), $auto_unserialize = false){
		$data = array();
		$where = !empty($skeys) ? ' WHERE '.DB::field($this->_pk, $skeys) : '';
		$query = DB::query('SELECT * FROM '.DB::table($this->_table).$where);
		while($value = DB::fetch($query)) {
			$data[$value['skey']] = $auto_unserialize ? (array)dunserialize($value['svalue']) : $value['svalue'];
		}
		return $data;
	}

	public function update_setting($skey, $svalue){
		return DB::insert($this->_table, array($this->_pk => $skey, 'svalue' => is_array($svalue) ? serialize($svalue) : $svalue), false, true);
	}

	public function update_batch($array) {
		$settings = array();
		foreach($array as $key => $value) {
			$key = addslashes($key);
			$value = addslashes(is_array($value) ? serialize($value) : $value);
			$settings[] = "('$key', '$value')";
		}
		if($settings) {
			return DB::query("REPLACE INTO ".DB::table('common_setting')." (`skey`, `svalue`) VALUES ".implode(',', $settings));
		}
		return false;
	}

	public function skey_exists($skey) {
		return DB::result_first('SELECT skey FROM %t WHERE skey=%s LIMIT 1', array($this->_table, $skey)) ? true : false;
	}

	public function fetch_all_not_key($skey) {
		return DB::fetch_all('SELECT * FROM '.DB::table($this->_table).' WHERE skey NOT IN('.dimplode($skey).')');
	}

	public function fetch_all_table_status() {
		return DB::fetch_all('SHOW TABLE STATUS');
	}

	public function get_tablepre() {
		return DB::object()->tablepre;
	}

	public function update_count($skey, $num) {
		return DB::query("UPDATE %t SET svalue = svalue + %d WHERE skey = %s", array($this->_table, $num, $skey), false, true);
	}

}

?>