<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: payment_alipay.php 36342 2021-05-17 14:15:14Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('SDK_ALIPAY_GATEWAYURL', 'https://openapi.alipay.com/gateway.do');

require DISCUZ_ROOT . './api/payment/payment_base.php';

class payment_alipay extends payment_base {

	public function __construct() {
		global $_G;
		$this->settings = C::t('common_setting')->fetch_setting('ec_alipay', true);
		$this->notify_url = $_G['siteurl'] . 'api/payment/notify/notify_alipay.php';
		parent::__construct();
	}

	public function pay($order) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		if(defined('IN_MOBILE')) {
			return $this->alipay_trade_wap_pay($order);
		} else {
			return $this->alipay_trade_page_pay($order);
		}
	}

	public function status($out_biz_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		return $this->alipay_trade_query($out_biz_no);
	}

	public function refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		return $this->alipay_refund($refund_no, $trade_no, $refund_amount, $refund_desc);
	}

	public function refund_status($refund_no, $trade_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		return $this->alipay_refund_status($refund_no, $trade_no);
	}

	public function transfer($transfer_no, $amount, $realname, $account, $title = '', $desc = '') {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		return $this->alipay_fund_trans_uni_transfer($transfer_no, $amount, $realname, $account, $title, $desc);
	}

	public function transfer_status($transfer_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		return $this->alipay_fund_trans_order_query($transfer_no);
	}

	public function alipay_sign_verify($sign, $data) {
		if(!$data) {
			return false;
		}
		if($this->settings['ec_alipay_sign_mode']) {
			$public_key = $this->settings['mode_b_alipay_cert'];
		} else {
			$public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->settings['mode_a_alipay_public_key'], 64, "\n", true) . "\n-----END PUBLIC KEY-----";
		}

		$data = $this->alipay_make_notify_signstr($data);

		$public_keyres = openssl_pkey_get_public($public_key);
		$result = (openssl_verify($data, base64_decode($sign), $public_keyres, OPENSSL_ALGO_SHA256) === 1);
		openssl_free_key($public_keyres);
		return $result;
	}

	private function alipay_trade_query($out_biz_no) {
		global $_G;
		$data = array(
			'method' => 'alipay.trade.query',
			'charset' => 'utf-8',
			'sign_type' => 'RSA2',
			'format' => 'JSON',
			'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'),
			'version' => '1.0',
			'biz_content' => json_encode(array('out_trade_no' => $out_biz_no))
		);
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		$res = json_decode($res, true);
		$res = $res['alipay_trade_query_response'];
		if($res['code'] == 10000) {
			if($res['trade_status'] == 'TRADE_SUCCESS') {
				return array('code' => 200, 'data' => array('trade_no' => $res['trade_no'], 'payment_time' => strtotime($res['send_pay_date'])));
			} else {
				return array('code' => 500, 'message' => $res['trade_status']);
			}
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8')) {
				$res['sub_msg'] = diconv($res['sub_msg'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['sub_code'], 'message' => $res['sub_msg']);
		}
	}

	private function alipay_trade_page_pay($order) {
		global $_G;

		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}

		$data = array(
			'method' => 'alipay.trade.page.pay',
			'charset' => 'utf-8',
			'sign_type' => 'RSA2',
			'format' => 'JSON',
			'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'),
			'version' => '1.0',
			'biz_content' => json_encode(array(
				'out_trade_no' => $order['out_biz_no'],
				'product_code' => 'FAST_INSTANT_TRADE_PAY',
				'total_amount' => $order['amount'] / 100,
				'subject' => $order['subject'],
				'body' => $order['description'],
				'timeout_express' => '1d',
				'qr_pay_mode' => '2',
				'integration_type' => 'PCWEB'
			))
		);
		if($this->notify_url) {
			$data['notify_url'] = $this->notify_url;
		}
		if($order['return_url']) {
			$data['return_url'] = $order['return_url'];
		}
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		if(strtoupper($_G['charset'] != 'GBK')) {
			$res = diconv($res, 'GB2312', $_G['charset']);
		}
		if(preg_match('/^https?:\/\/.+$/', $res)) {
			return array('code' => 200, 'url' => $res);
		} else {
			if(preg_match('/<div\s+class="Todo">([^<]+)<\/div>/i', $res, $matchers)) {
				return array('code' => 500, 'message' => $matchers[1]);
			} else {
				return array('code' => 501, 'message' => $res);
			}
		}
	}

	private function alipay_trade_wap_pay($order) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}

		$data = array(
			'method' => 'alipay.trade.wap.pay',
			'format' => 'JSON',
			'charset' => 'utf-8',
			'sign_type' => 'RSA2',
			'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'),
			'version' => '1.0',
			'biz_content' => json_encode(array(
				'out_trade_no' => $order['out_biz_no'],
				'product_code' => 'FAST_INSTANT_TRADE_PAY',
				'total_amount' => $order['amount'] / 100,
				'subject' => $order['subject'],
				'body' => $order['description'],
				'timeout_express' => '1d',
				'qr_pay_mode' => '2',
				'integration_type' => 'PCWEB'
			))
		);
		if($this->notify_url) {
			$data['notify_url'] = $this->notify_url;
		}
		if($order['return_url']) {
			$data['return_url'] = $order['return_url'];
		}
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		if($order['referer_url']) {
			$data['return_url'] = $order['referer_url'];
			$data['quit_url'] = $order['referer_url'];
		} else {
			$data['quit_url'] = $_G['siteurl'];
		}

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		if(!preg_match('/^https?:\/\/.+$/', $res) && strtoupper($_G['charset'] != 'GBK')) {
			$res = diconv($res, 'GB2312', $_G['charset']);
		}
		if(preg_match('/^https?:\/\/.+$/', $res)) {
			return array('code' => 200, 'url' => $res);
		} else {
			if(preg_match('/<div\s+class="Todo">([^<]+)<\/div>/i', $res, $matchers)) {
				return array('code' => 500, 'message' => $matchers[1]);
			} else {
				return array('code' => 501, 'message' => $res);
			}
		}
	}

	private function alipay_fund_trans_uni_transfer($transfer_no, $amount, $realname, $account, $title = '', $desc = '') {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$title = diconv($title, $_G['charset'], 'UTF-8');
			$desc = diconv($desc, $_G['charset'], 'UTF-8');
		}
		if(!$this->settings['ec_alipay_sign_mode']) {
			return array('code' => 500, 'message' => 'not support sign mode.');
		}

		$data = array(
			'app_id' => $this->settings['mode_b_appid'],
			'method' => 'alipay.fund.trans.uni.transfer',
			'format' => 'JSON',
			'charset' => 'utf-8',
			'sign_type' => 'RSA2',
			'app_cert_sn' => $this->alipay_cert_sn($this->settings['mode_b_app_cert']),
			'alipay_root_cert_sn' => $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']),
			'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'),
			'version' => '1.0'
		);
		$biz_content = array(
			'out_biz_no' => $transfer_no,
			'trans_amount' => sprintf('%.2f', $amount / 100),
			'product_code' => 'TRANS_ACCOUNT_NO_PWD',
			'biz_scene' => 'DIRECT_TRANSFER',
			'payee_info' => array('identity' => $account, 'identity_type' => 'ALIPAY_LOGON_ID', 'name' => $realname)
		);
		if($title) {
			$biz_content['order_title'] = $title;
		}
		if($desc) {
			$biz_content['remark'] = $desc;
		}
		$data['biz_content'] = json_encode($biz_content);

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($this->settings['mode_b_app_private_key'], $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		$res = json_decode($res, true);
		$res = $res['alipay_fund_trans_uni_transfer_response'];
		if($res['code'] == 10000) {
			if($res['status'] == 'SUCCESS') {
				return array('code' => 200, 'data' => array('transfer_time' => strtotime($res['trans_date'])));
			} elseif($res['status'] == 'DEALING') {
				return array('code' => 201, 'message' => 'DEALING');
			} else {
				return array('code' => 500, 'message' => $res['status']);
			}
		} else {
			if($res['sub_msg'] && strtoupper($_G['charset'] != 'UTF-8')) {
				$res['sub_msg'] = diconv($res['sub_msg'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['sub_code'], 'message' => $res['sub_msg']);
		}
	}

	private function alipay_fund_trans_order_query($transfer_no) {
		global $_G;
		$data = array('method' => 'alipay.fund.trans.order.query', 'format' => 'JSON', 'charset' => 'utf-8', 'sign_type' => 'RSA2', 'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'), 'version' => '1.0',);
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		$biz_content = array('out_biz_no' => $transfer_no,);
		$data['biz_content'] = json_encode($biz_content);

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		$res = json_decode($res, true);
		$res = $res['alipay_fund_trans_order_query_response'];
		if($res['code'] == 10000) {
			if($res['status'] == 'SUCCESS') {
				return array('code' => 200, 'data' => array('transfer_time' => strtotime($res['trans_date'])));
			} elseif($res['status'] == 'DEALING') {
				return array('code' => 201, 'message' => 'DEALING');
			} else {
				return array('code' => 500, 'message' => $res['status']);
			}
		} else {
			if($res['sub_msg'] && strtoupper($_G['charset'] != 'UTF-8')) {
				$res['sub_msg'] = diconv($res['sub_msg'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['sub_code'], 'message' => $res['sub_msg']);
		}
	}

	private function alipay_refund($refund_no, $trade_no, $amount, $refund_desc) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$refund_desc = diconv($refund_desc, $_G['charset'], 'UTF-8');
		}
		$data = array('method' => 'alipay.trade.refund', 'format' => 'JSON', 'charset' => 'utf-8', 'sign_type' => 'RSA2', 'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'), 'version' => '1.0', 'biz_content' => json_encode(array('trade_no' => $trade_no, 'refund_amount' => $amount / 100, 'out_request_no' => $refund_no, 'refund_reason' => $refund_desc)),);
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		$res = json_decode($res, true);
		$res = $res['alipay_trade_refund_response'];
		if($res['code'] == 10000) {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8')) {
				$res['sub_msg'] = diconv($res['sub_msg'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['sub_code'], 'message' => $res['sub_msg']);
		}
	}

	private function alipay_refund_status($refund_no, $trade_no) {
		global $_G;
		$data = array('method' => 'alipay.trade.fastpay.refund.query', 'format' => 'JSON', 'charset' => 'utf-8', 'sign_type' => 'RSA2', 'timestamp' => dgmdate(time(), 'Y-m-d H:i:s'), 'version' => '1.0', 'biz_content' => json_encode(array('trade_no' => $trade_no, 'out_request_no' => $refund_no,)),);
		if($this->settings['ec_alipay_sign_mode']) {
			$appid = $this->settings['mode_b_appid'];
			$private_key = $this->settings['mode_b_app_private_key'];
			$data['app_cert_sn'] = $this->alipay_cert_sn($this->settings['mode_b_app_cert']);
			$data['alipay_root_cert_sn'] = $this->alipay_root_cert_sn($this->settings['mode_b_alipay_root_cert']);
		} else {
			$appid = $this->settings['mode_a_appid'];
			$private_key = $this->settings['mode_a_app_private_key'];
		}
		$data['app_id'] = $appid;

		$signstr = $this->alipay_make_signstr($data);
		$data['sign'] = $this->alipay_sign($private_key, $signstr);
		$api = SDK_ALIPAY_GATEWAYURL . '?' . http_build_query($data);
		$res = $this->alipay_request($api);
		$res = json_decode($res, true);
		$res = $res['alipay_trade_fastpay_refund_query_response'];
		if($res['code'] == 10000) {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8')) {
				$res['sub_msg'] = diconv($res['sub_msg'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['sub_code'], 'message' => $res['sub_msg']);
		}
	}

	private function alipay_make_signstr($data) {
		ksort($data);
		$signstr = array();
		foreach($data as $key => $value) {
			$signstr[] = $key . '=' . $value;
		}
		$signstr = implode('&', $signstr);
		return $signstr;
	}

	private function alipay_sign($private_key, $data) {
		$private_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($private_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
		openssl_sign($data, $sign, $private_key, OPENSSL_ALGO_SHA256);
		return base64_encode($sign);
	}

	private function alipay_make_notify_signstr($data) {
		ksort($data);
		$signstr = array();
		foreach($data as $key => $value) {
			if(in_array($key, array('sign', 'sign_type')) || !$value) {
				continue;
			}
			if(is_array($value)) {
				$value = json_encode($value);
			}
			$signstr[] = $key . '=' . $value;
		}
		$signstr = implode('&', $signstr);
		return $signstr;
	}

	private function alipay_array_to_string($data) {
		$str = [];
		foreach($data as $name => $value) {
			$str[] = $name . '=' . $value;
		}
		return implode(',', $str);
	}

	private function alipay_cert_sn($appcert) {
		$ssl = openssl_x509_parse($appcert);
		$sn = md5($this->alipay_array_to_string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
		return $sn;
	}

	private function alipay_root_cert_sn($alipayrootcert) {
		$array = explode("-----END CERTIFICATE-----", $alipayrootcert);
		$sn = array();
		for($i = 0; $i < count($array) - 1; $i++) {
			$ssl = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
			if($ssl['signatureTypeLN'] == "sha256WithRSAEncryption") {
				$sn[] = md5($this->alipay_array_to_string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
			}
		}
		return implode('_', $sn);
	}

	private function alipay_request($api, $post = array()) {
		$client = filesock::open(array(
			'url' => $api,
			'method' => 'POST',
			'post' => $post
		));

		$data = $client->request();

		if($client->curlstatus['http_code'] == 200) {
			return $data;
		} elseif(preg_match('/^30\d+$/', $client->curlstatus['http_code'])) {
			return $client->curlstatus['redirect_url'];
		} else {
			return;
		}
	}
}
