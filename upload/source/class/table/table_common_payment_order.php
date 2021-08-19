<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_payment_order.php 36342 2021-05-17 15:14:35Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_payment_order extends discuz_table
{
	public function __construct() {
		$this->_table = 'common_payment_order';
		$this->_pk = 'id';
		parent::__construct();
	}

	public function update_order_finish($id, $trade_no, $payment_time, $channel) {
		DB::query("UPDATE %t SET `trade_no` = %s,`payment_time` = %d, `channel` = %s, `status` = %d WHERE `id` = %d AND `status` = 0", array($this->_table, $trade_no, $payment_time, $channel, 1, $id));
		return DB::affected_rows();
	}

	public function count_by_search($uid, $optype, $begintime, $endtime, $out_biz_no = '', $channel = '', $status = '') {
		$condition = $this->make_query_condition($uid, $optype, $begintime, $endtime, $out_biz_no, $channel, $status);
		return DB::result_first('SELECT COUNT(*) FROM %t ' . $condition[0], $condition[1]);
	}

	public function fetch_by_biz_no($out_biz_no) {
		return DB::fetch_first("SELECT * FROM %t WHERE out_biz_no = %s", array($this->_table, $out_biz_no));
	}

	public function fetch_type_all($uid = 0) {
		$wherestr = '';
		if($uid) {
			$wherestr = 'WHERE `uid` = ' . intval($uid);
		}
		$query = DB::query("SELECT `type`, `type_name` FROM %t $wherestr GROUP BY `type`", array($this->_table));
		$result = array();
		while($item = DB::fetch($query)) {
			$result[$item['type']] = $item['type_name'];
		}
		return $result;
	}

	public function fetch_all_by_search($uid, $optype, $begintime = 0, $endtime = 0, $out_biz_no = '', $channel = '', $status = '', $start = 0, $limit = 0) {
		$condition = $this->make_query_condition($uid, $optype, $begintime, $endtime, $out_biz_no, $channel, $status);
		return DB::fetch_all('SELECT * FROM %t ' . $condition[0] . ' ORDER BY id DESC ' . DB::limit($start, $limit), $condition[1], 'id');
	}

	private function make_query_condition($uid, $optype, $begintime = 0, $endtime = 0, $out_biz_no = '', $channel = '', $status = '') {
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
		if($optype) {
			$wherearr[] = is_array($optype) && $optype ? '`type` IN (%n)' : '`type` = %s';
			$parameter[] = $optype != -1 ? $optype : '';
		}
		if($channel) {
			$wherearr[] = 'channel = %s';
			$parameter[] = $channel;
		}
		if($status !== '') {
			if($status == 2) {
				$wherearr[] = '`status` = 0 AND `expire_time` < %d';
				$parameter[] = time();
			} else {
				$wherearr[] = '`status` = %d';
				$parameter[] = $status;
			}
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