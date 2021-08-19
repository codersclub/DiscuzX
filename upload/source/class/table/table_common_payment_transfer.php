<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_payment_transfer.php 36342 2021-05-17 15:17:43Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_payment_transfer extends discuz_table
{
	public function __construct() {
		$this->_table = 'common_payment_transfer';
		$this->_pk = 'id';
		parent::__construct();
	}

	public function fetch_by_no($transfer_no) {
		return DB::fetch_first("SELECT * FROM %t WHERE out_biz_no = %s", array($this->_table, $transfer_no));
	}

	public function update_transfer_by_no($transfer_no, $data) {
		DB::update($this->_table, $data, DB::field('out_biz_no', $transfer_no));
	}

	public function count_by_search($uid, $begintime, $endtime, $out_biz_no = '', $channel = '', $status = '') {
		$condition = $this->make_query_condition($uid, $begintime, $endtime, $out_biz_no, $channel, $status);
		return DB::result_first('SELECT COUNT(*) FROM %t ' . $condition[0], $condition[1]);
	}

	public function fetch_all_by_search($uid, $begintime = 0, $endtime = 0, $out_biz_no = '', $channel = '', $status = '', $start = 0, $limit = 0) {
		$condition = $this->make_query_condition($uid, $begintime, $endtime);
		return DB::fetch_all('SELECT * FROM %t ' . $condition[0] . ' ORDER BY id DESC ' . DB::limit($start, $limit), $condition[1], 'id');
	}

	private function make_query_condition($uid, $begintime = 0, $endtime = 0, $out_biz_no = '', $channel = '', $status = '') {
		$wherearr = array();
		$parameter = array($this->_table);
		if($out_biz_no) {
			$wherearr[] = 'out_biz_no = %s';
			$parameter[] = $out_biz_no;
		}
		if($uid) {
			$uid = dintval($uid, true);
			$wherearr[] = is_array($uid) && $uid ? 'uid IN (%n)' : 'uid = %d';
			$parameter[] = $uid;
		}
		if($channel) {
			$wherearr[] = 'channel = %s';
			$parameter[] = $channel;
		}
		if($status !== '') {
			$wherearr[] = 'status = %d';
			$parameter[] = $status;
		}
		if($begintime) {
			$wherearr[] = 'dateline > %d';
			$parameter[] = dmktime($begintime);
		}
		if($endtime) {
			$wherearr[] = 'dateline < %d';
			$parameter[] = dmktime($endtime);
		}
		$wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE ' . implode(' AND ', $wherearr) : '';
		return array($wheresql, $parameter);
	}
}

?>