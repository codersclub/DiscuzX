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

define('SDK_QPAY_PAY_UNIFIEDORDER', 'https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi');
define('SDK_QPAY_PAY_ORDERQUERY', 'https://qpay.qq.com/cgi-bin/pay/qpay_order_query.cgi');
define('SDK_QPAY_PAY_REFUND', 'https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi');
define('SDK_QPAY_PAY_REFUNDQUERY', 'https://qpay.qq.com/cgi-bin/pay/qpay_refund_query.cgi');

require DISCUZ_ROOT . './api/payment/payment_base.php';

class payment_qpay extends payment_base {

	public function __construct() {
		global $_G;
		$this->settings = C::t('common_setting')->fetch_setting('ec_qpay', true);
		$this->notify_url = $_G['siteurl'] . 'api/payment/notify/notify_qpay.php';
		parent::__construct();
	}

	public function pay($order) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'This payment method is not open yet.');
		}
		return $this->qpay_unifiedorder_pay($order);
	}

	public function status($out_biz_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'This payment method is not open yet.');
		}
		return $this->qpay_order_query($out_biz_no);
	}

	public function refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'This payment method is not open yet.');
		}
		return $this->qpay_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc);
	}

	public function refund_status($refund_no, $trade_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'This payment method is not open yet.');
		}
		return $this->qpay_refund_status($refund_no);
	}

	public function transfer($transfer_no, $amount, $realname, $account, $title = '', $desc = '') {
		return array('code' => 500, 'message' => 'This payment method is not support this feature.');
	}

	public function transfer_status($transfer_no) {
		return array('code' => 500, 'message' => 'This payment method is not support this feature.');
	}

	public function pay_jsapi($order) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'This payment method is not open yet.');
		}
		return $this->qpay_unifiedorder_pay($order, 'JSAPI');
	}

	public function qpay_sign_verify() {
		$xml = file_get_contents('php://input');
		$data = $this->qpay_x2o($xml);
		$sign = $this->qpay_sign($this->settings['v1_key'], $data, 1);
		if($sign != $data['sign']) {
			return array('code' => 50001, 'data' => $data);
		}
		if($data['return_code'] != 'SUCCESS') {
			return array('code' => 50002, 'data' => $data);
		}
		if($data['result_code'] != 'SUCCESS') {
			return array('code' => 50003, 'data' => $data);
		}
		return array('code' => 200, 'data' => $data);
	}

	protected function enable() {
		return $this->settings && $this->settings['on'];
	}

	private function qpay_unifiedorder_pay($order, $type = 'NATIVE') {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}
		$data = array('appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->qpay_nonce(), 'body' => $order['subject'], 'attach' => $order['description'], 'out_trade_no' => $order['out_biz_no'], 'fee_type' => 'CNY', 'total_fee' => intval($order['amount']), 'spbill_create_ip' => $_G['clientip'], 'time_expire' => dgmdate(time() + 86400, 'YmdHis'), 'notify_url' => $this->notify_url, 'trade_type' => $type);
		$data['sign'] = $this->qpay_sign($this->settings['v1_key'], $data);
		$data = $this->qpay_o2x($data);

		$api = SDK_QPAY_PAY_UNIFIEDORDER;
		$res = $this->qpay_request_xml($api, $data);
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->qpay_x2o($res);

		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 501, 'message' => $res['err_code_des']);
		} else {
			return array('code' => 200, 'url' => isset($res['code_url']) ? $res['code_url'] : $res['prepay_id']);
		}
	}

	private function qpay_order_query($out_biz_no) {
		global $_G;
		$data = array('appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'out_trade_no' => $out_biz_no, 'nonce_str' => $this->qpay_nonce());
		$data['sign'] = $this->qpay_sign($this->settings['v1_key'], $data);
		$data = $this->qpay_o2x($data);
		$api = SDK_QPAY_PAY_ORDERQUERY;
		$res = $this->qpay_request_xml($api, $data);

		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->qpay_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code_des']);
		} else{
			$pay_time = strtotime(preg_replace('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $res['time_end']));
			return array('code' => 200, 'data' => array('trade_no' => $res['transaction_id'], 'payment_time' => $pay_time));
		}
	}

	private function qpay_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$refund_desc = diconv($refund_desc, $_G['charset'], 'UTF-8');
		}
		// 此处与微信支付不同, op_user_id, op_user_passwd 为 操作员 ID 以及 操作员密码的 MD5
		$data = array('appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->qpay_nonce(), 'transaction_id' => $trade_no, 'out_refund_no' => $refund_no, 'refund_fee' => $refund_amount, 'op_user_id' => $this->settings['op_user_id'], 'op_user_passwd' => $this->settings['op_user_passwd']);
		$data['sign'] = $this->qpay_sign($this->settings['v1_key'], $data);
		$data = $this->qpay_o2x($data);
		$api = SDK_QPAY_PAY_REFUND;
		$res = $this->qpay_request_xml($api, $data, true);

		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->qpay_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code_des']);
		} else {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		}
	}

	private function qpay_refund_status($refund_no) {
		global $_G;
		$data = array('appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->qpay_nonce(), 'out_refund_no' => $refund_no);
		$data['sign'] = $this->qpay_sign($this->settings['v1_key'], $data);
		$data = $this->qpay_o2x($data);
		$api = SDK_QPAY_PAY_REFUNDQUERY;
		$res = $this->qpay_request_xml($api, $data);
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->qpay_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code'] . '-' . $res['err_code_des']);
		} else {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		}
	}

	private function qpay_o2x($data) {
		$xml = '<xml>';
		foreach($data as $key => $value) {
			$xml .= "\n<{$key}>{$value}</{$key}>";
		}
		$xml .= "\n</xml>";
		return $xml;
	}

	private function qpay_x2o($xml) {
		libxml_disable_entity_loader(true);
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $data;
	}

	private function qpay_sign($token, $data, $sign = 0) {
		ksort($data);
		$signstr = '';
		foreach($data as $key => $value) {
			if(!$value || ($sign && $key == 'sign')) continue;
			$signstr .= $key . '=' . $value . '&';
		}
		$signstr .= 'key=' . $token;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}

	private function qpay_nonce() {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for($i = 0; $i < 32; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	private function qpay_request_xml($api, $xml, $cert = false) {
		$params = array(
			'url' => $api,
			'method' => 'POST',
			'rawdata' => $xml,
			'encodetype' => 'application/xml',
		);

		if($cert) {
			if(!$this->settings['v1_cert_path'] || !file_exists(DISCUZ_ROOT . $this->settings['v1_cert_path']) || !is_file(DISCUZ_ROOT . $this->settings['v1_cert_path'])) {
				return '<xml><return_code>400</return_code><return_msg>p12 not found.</return_msg></xml>';
			}
			$params['verifypeer'] = $this->settings['v1_cert_path'];
		}

		$client = filesock::open($params);
		$data = $client -> request();
		if(!$data) {
			$data = $client -> filesockbody;
		}
		return $data;
	}

}