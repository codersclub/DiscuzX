<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_payment_refund.php 36342 2021-05-17 15:17:15Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_payment_refund extends discuz_table
{
	public function __construct() {
		$this->_table = 'common_payment_refund';
		$this->_pk = 'id';
		parent::__construct();
	}

	public function fetch_by_no($refund_no) {
		return DB::fetch_first("SELECT * FROM %t WHERE out_biz_no = %s", array($this->_table, $refund_no));
	}

	public function update_refund_by_no($refund_no, $data) {
		DB::update($this->_table, $data, DB::field('out_biz_no', $refund_no));
	}

	public function sum_by_orders($ids) {
		return DB::fetch_all("SELECT `order_id`, sum(`amount`) as `amount` FROM %t WHERE `order_id` in (%n) AND `status` = 2 GROUP BY `order_id`", array($this->_table, $ids), 'order_id');
	}
}

?>