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

	private $_archiver_table = 'common_smslog_archive';

	public function __construct() {

		$this->_table = 'common_smslog';
		$this->_pk    = 'smslogid';

		parent::__construct();
	}

	public function get_lastsms_by_uumm($uid, $svctype, $secmobicc, $secmobile) {
		return DB::fetch_first("SELECT * FROM %t WHERE uid = %d AND svctype = %d AND secmobicc = %d AND secmobile = %d ORDER BY dateline DESC", array($this->_table, $uid, $svctype, $secmobicc, $secmobile));
	}

	public function get_sms_by_ut($uid, $time) {
		$dateline = time() - $time;
		return DB::fetch_all("SELECT dateline FROM %t WHERE uid = %d AND dateline > %d", array($this->_table, $uid, $dateline));
	}

	public function get_sms_by_mmt($secmobicc, $secmobile, $time) {
		$dateline = time() - $time;
		return DB::fetch_all("SELECT dateline FROM %t WHERE secmobicc = %d AND secmobile = %d AND dateline > %d", array($this->_table, $secmobicc, $secmobile, $dateline));
	}

	public function count_sms_by_milions_mmt($secmobicc, $secmobile, $time) {
		$dateline = time() - $time;
		$secmobile = substr($secmobile, 0, -4);
		return DB::result_first("SELECT COUNT(*) FROM %t WHERE secmobicc = %d AND secmobile LIKE %d AND dateline > %d", array($this->_table, $secmobicc, $secmobile, $dateline));
	}

	public function count_sms_by_time($time) {
		$dateline = time() - $time;
		return DB::result_first("SELECT COUNT(*) FROM %t WHERE dateline > %d", array($this->_table, $dateline));
	}

	public function fetch_all_by_dateline($dateline, $glue = '>=') {
		$glue = helper_util::check_glue($glue);
		return DB::fetch_all("SELECT * FROM %t WHERE dateline{$glue}%d ORDER BY dateline", array($this->_table, $dateline), $this->_pk);
	}

	public function insert_archiver($data) {
		if(!empty($data) && is_array($data)) {
			return DB::insert($this->_archiver_table, $data, false, true);
		}
		return 0;
	}

}

?>