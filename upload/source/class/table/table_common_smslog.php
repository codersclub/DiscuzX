<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id$
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_smslog extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_smslog';
		$this->_pk    = 'smslogid';

		parent::__construct();
	}

	public function get_lastsms_by_uumm($uid, $type, $secmobicc, $secmobile) {
		return DB::fetch_first("SELECT * FROM %t WHERE uid = %d AND type = %d AND secmobicc = %d AND secmobile = %d ORDER BY sendtime DESC", array($this->_table, $uid, $type, $secmobicc, $secmobile));
	}

	public function get_sms_by_ut($uid, $time) {
		$time = time() - $time;
		return DB::fetch_all("SELECT sendtime FROM %t WHERE uid = %d AND sendtime > %d", array($this->_table, $uid, $time));
	}

	public function get_sms_by_mmt($secmobicc, $secmobile, $time) {
		$time = time() - $time;
		return DB::fetch_all("SELECT sendtime FROM %t WHERE secmobicc = %d AND secmobile = %d AND sendtime > %d", array($this->_table, $secmobicc, $secmobile, $time));
	}

	public function count_sms_by_milions_mmt($secmobicc, $secmobile, $time) {
		$time = time() - $time;
		$secmobile = substr($secmobile, 0, -4);
		return DB::result_first("SELECT COUNT(*) FROM %t WHERE secmobicc = %d AND secmobile LIKE %d AND sendtime > %d", array($this->_table, $secmobicc, $secmobile, $time));
	}

	public function count_sms_by_time($time) {
		$time = time() - $time;
		return DB::result_first("SELECT COUNT(*) FROM %t WHERE sendtime > %d", array($this->_table, $time));
	}

}

?>