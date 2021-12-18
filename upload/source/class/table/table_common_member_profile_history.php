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

class table_common_member_profile_history extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_member_profile_history';
		$this->_pk    = 'hid';

		parent::__construct();
	}

	public function fetch_all_by_uid($uid) {
		return DB::fetch_all('SELECT * FROM %t WHERE uid=%d ORDER BY dateline', array($this->_table, $uid), $this->_pk);
	}
}