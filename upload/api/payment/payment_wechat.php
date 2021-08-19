<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: payment_wechat.php 36342 2021-05-17 14:15:31Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('SDK_WEIXIN_PAY_UNIFIEDORDER', 'https://api.mch.weixin.qq.com/pay/unifiedorder');
define('SDK_WEIXIN_PAY_ORDERQUERY', 'https://api.mch.weixin.qq.com/pay/orderquery');
define('SDK_WEIXIN_PAY_REFUND', 'https://api.mch.weixin.qq.com/secapi/pay/refund');
define('SDK_WEIXIN_PAY_REFUNDQUERY', 'https://api.mch.weixin.qq.com/pay/refundquery');
define('SDK_WEIXIN_AUTHORIZE', 'https://open.weixin.qq.com/connect/oauth2/authorize');
define('SDK_WEIXIN_SNS_ACCESS_TOKEN', 'https://api.weixin.qq.com/sns/oauth2/access_token');

define('SDK_WEIXIN_PAY_V3_TRANSACTIONS_NATIVE', 'https://api.mch.weixin.qq.com/v3/pay/transactions/native');
define('SDK_WEIXIN_PAY_V3_TRANSACTIONS_H5', 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5');
define('SDK_WEIXIN_PAY_V3_TRANSACTIONS_JSAPI', 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi');
define('SDK_WEIXIN_PAY_V3_TRANSACTIONS_OUTTRADENO', 'https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/');
define('SDK_WEIXIN_PAY_V3_REFUND_DOMESTIC_REFUNDS', 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds');
define('SDK_WEIXIN_PAY_V3_REFUND_DOMESTIC_REFUNDS_QUERY', 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds/');
define('SDK_WEIXIN_PAY_V3_CERTIFICATES', 'https://api.mch.weixin.qq.com/v3/certificates');

require DISCUZ_ROOT . './api/payment/payment_base.php';

class payment_wechat extends payment_base {

	public function __construct() {
		global $_G;
		$this->settings = C::t('common_setting')->fetch_setting('ec_wechat', true);
		$this->notify_url = $_G['siteurl'] . 'api/payment/notify/notify_wechat.php';
		parent::__construct();
	}

	public function pay($order) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}

		$device = $this->wechat_device();
		if($device) {
			if($this->settings['ec_wechat_version']) {
				return $this->v3_wechat_h5_pay($order);
			} else {
				return $this->wechat_unifiedorder_pay($order, 'MWEB');
			}
		} else {
			if($this->settings['ec_wechat_version']) {
				return $this->v3_wechat_native_pay($order);
			} else {
				return $this->wechat_unifiedorder_pay($order, 'NATIVE');
			}
		}
	}

	public function status($out_biz_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		if($this->settings['ec_wechat_version']) {
			return $this->v3_wechat_query_order($out_biz_no);
		} else {
			return $this->wechat_order_query($out_biz_no);
		}
	}

	public function refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		if($this->settings['ec_wechat_version']) {
			return $this->v3_wechat_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc);
		} else {
			return $this->wechat_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc);
		}
	}

	public function refund_status($refund_no, $trade_no) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		if($this->settings['ec_wechat_version']) {
			return $this->v3_wechat_refund_query($refund_no);
		} else {
			return $this->wechat_refund_status($refund_no);
		}
	}

	public function transfer($transfer_no, $amount, $realname, $account, $title = '', $desc = '') {
		return array('code' => 500, 'message' => 'not support.');
	}

	public function transfer_status($transfer_no) {
		return array('code' => 500, 'message' => 'not support.');
	}

	public function pay_jsapi($order, $openid) {
		if(!$this->enable()) {
			return array('code' => 500, 'message' => 'Did not open payment');
		}
		if($this->settings['ec_wechat_version']) {
			return $this->v3_wechat_h5_jsapi($order, $openid);
		} else {
			return $this->wechat_unifiedorder_pay($order, 'JSAPI', $openid);
		}
	}

	public function wechat_jsapidata($prepay_id) {
		if($this->settings['ec_wechat_version']) {
			$jsapidata = array(
				'appId' => $this->settings['appid'],
				'timeStamp' => time() . '',
				'nonceStr' => $this->wechat_nonce(),
				'package' => 'prepay_id=' . $prepay_id,
				'signType' => 'RSA'
			);
			$jsapidata['paySign'] = $this->v3_wechat_jsapi_authorization($jsapidata);
		} else {
			$jsapidata = array('appId' => $this->settings['appid'], 'timeStamp' => time() . '', 'nonceStr' => $this->wechat_nonce(), 'package' => 'prepay_id=' . $prepay_id, 'signType' => 'MD5');
			$jsapidata['paySign'] = $this->wechat_sign($this -> settings['v1_key'], $jsapidata);
		}
		return json_encode($jsapidata);
	}

	public function wechat_authorize($redirect_uri, $state, $scope = 'snsapi_base') {
		$appid = $this->settings['appid'];
		$redirect_uri = urlencode($redirect_uri);
		return SDK_WEIXIN_AUTHORIZE . "?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
	}

	public function wechat_access_token_by_code($code) {
		$appid = $this->settings['appid'];
		$appsecret = $this->settings['appsecret'];
		$api = SDK_WEIXIN_SNS_ACCESS_TOKEN . "?appid={$appid}&secret={$appsecret}&code=$code&grant_type=authorization_code";
		return $this->wechat_request($api);
	}

	public function wechat_sign_verify() {
		$xml = file_get_contents('php://input');
		$data = $this->wechat_x2o($xml);
		$sign = $this->wechat_sign($this->settings['v1_key'], $data, 1);
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

	public function v3_wechat_sign_verify() {
		$nonce = $_SERVER['HTTP_WECHATPAY_NONCE'];
		$timestamp = $_SERVER['HTTP_WECHATPAY_TIMESTAMP'];
		$serial = $_SERVER['HTTP_WECHATPAY_SERIAL'];
		$json = file_get_contents('php://input');
		$signature = $_SERVER['HTTP_WECHATPAY_SIGNATURE'];

		$serial = strtoupper(ltrim($serial, '0'));
		$public_key = $this->settings['v3_certificates'][$serial];
		if(!$public_key) {
			return array('code' => 50001, 'data' => $json);
		}
		$signature = base64_decode($signature);
		$signstr = $timestamp . "\n" . $nonce . "\n" . $json . "\n";
		if(!openssl_verify($signstr, $signature, $public_key, 'sha256WithRSAEncryption')) {
			return array('code' => 50002, 'data' => $json);
		}
		$resource = json_decode($json, true);
		if($resource['event_type'] != 'TRANSACTION.SUCCESS') {
			return array('code' => 50003, 'data' => $resource);
		}
		$resource = $resource['resource'];
		$data = $this->v3_wechat_decrypt2string($resource['associated_data'], $resource['nonce'], $resource['ciphertext']);
		return array('code' => 200, 'data' => $data);
	}

	public function v3_wechat_support() {
		// ext-sodium (default installed on >= PHP 7.2)
		if(function_exists('sodium_crypto_aead_aes256gcm_is_available') && sodium_crypto_aead_aes256gcm_is_available()) {
			return true;
		}
		// openssl (PHP >= 7.1 support AEAD)
		if(PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
			return true;
		}
		return false;
	}

	public function v3_wechat_certificates() {
		global $_G;
		$api = SDK_WEIXIN_PAY_V3_CERTIFICATES;
		$res = $this->v3_wechat_request_json($api, '', 'GET');
		$res = json_decode($res, true);
		$list = array();
		if($res['data']) {
			foreach($res['data'] as $item) {
				$serial_no = $item['serial_no'];
				$item = $item['encrypt_certificate'];
				$data = $this->v3_wechat_decrypt2string($item['associated_data'], $item['nonce'], $item['ciphertext']);
				$list[$serial_no] = $data;
			}
		}
		return array('code' => 200, 'data' => $list);
	}

	protected function enable() {
		if($this->settings && $this->settings['on']) {
			if($this->settings['ec_wechat_version']) {
				return $this->v3_wechat_support();
			}
			return true;
		} else {
			return false;
		}
	}

	private function wechat_unifiedorder_pay($order, $type = 'NATIVE', $openid = null) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}
		$data = array('appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->wechat_nonce(), 'sign_type' => 'MD5', 'body' => $order['subject'], 'detail' => $order['description'], 'out_trade_no' => $order['out_biz_no'], 'total_fee' => intval($order['amount']), 'spbill_create_ip' => $_G['clientip'], 'time_expire' => dgmdate(time() + 86400, 'YmdHis'), 'notify_url' => $this->notify_url, 'trade_type' => $type,);
		if($openid) {
			$data['openid'] = $openid;
		}
		$data['sign'] = $this->wechat_sign($this->settings['v1_key'], $data);
		$data = $this->wechat_o2x($data);

		$api = SDK_WEIXIN_PAY_UNIFIEDORDER;
		$res = $this->wechat_request_xml($api, $data);
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->wechat_x2o($res);

		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 501, 'message' => $res['err_code_des']);
		} else {
			if($res['code_url']) {
				$url = $res['code_url'];
			} elseif($res['mweb_url']) {
				$url = $res['mweb_url'];
			} else {
				$url = $res['prepay_id'];
			}
			return array('code' => 200, 'url' => $url);
		}
	}

	private function wechat_order_query($out_biz_no) {
		global $_G;
		$data = ['appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'out_trade_no' => $out_biz_no, 'nonce_str' => $this->wechat_nonce(), 'sign_type' => 'MD5'];
		$data['sign'] = $this->wechat_sign($this->settings['v1_key'], $data);
		$data = $this->wechat_o2x($data);
		$api = SDK_WEIXIN_PAY_ORDERQUERY;
		$res = $this->wechat_request_xml($api, $data);

		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->wechat_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code_des']);
		} else {
			$pay_time = strtotime(preg_replace('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $res['time_end']));
			return array('code' => 200, 'data' => array('trade_no' => $res['transaction_id'], 'payment_time' => $pay_time));
		}
	}

	private function wechat_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$refund_desc = diconv($refund_desc, $_G['charset'], 'UTF-8');
		}
		$data = ['appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->wechat_nonce(), 'sign_type' => 'MD5', 'transaction_id' => $trade_no, 'out_refund_no' => $refund_no, 'total_fee' => $total_amount, 'refund_fee' => $refund_amount, 'refund_desc' => $refund_desc];
		$data['sign'] = $this->wechat_sign($this->settings['v1_key'], $data);
		$data = $this->wechat_o2x($data);
		$api = SDK_WEIXIN_PAY_REFUND;
		$res = $this->wechat_request_xml($api, $data, true);

		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->wechat_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code_des']);
		} else {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		}
	}

	private function wechat_refund_status($refund_no) {
		global $_G;
		$data = ['appid' => $this->settings['appid'], 'mch_id' => $this->settings['mch_id'], 'nonce_str' => $this->wechat_nonce(), 'sign_type' => 'MD5', 'out_refund_no' => $refund_no,];
		$data['sign'] = $this->wechat_sign($this->settings['v1_key'], $data);
		$data = $this->wechat_o2x($data);
		$api = SDK_WEIXIN_PAY_REFUNDQUERY;
		$res = $this->wechat_request_xml($api, $data);
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$res = diconv($res, 'UTF-8', $_G['charset']);
		}
		$res = $this->wechat_x2o($res);
		if($res['return_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['return_msg']);
		} elseif($res['result_code'] != 'SUCCESS') {
			return array('code' => 500, 'message' => $res['err_code'] . '-' . $res['err_code_des']);
		} else {
			return array('code' => 200, 'data' => array('refund_time' => time()));
		}
	}

	private function v3_wechat_native_pay($order) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}
		$data = array('appid' => $this->settings['appid'], 'mchid' => $this->settings['mch_id'], 'description' => $order['subject'] . ': ' . $order['description'], 'out_trade_no' => $order['out_biz_no'], 'notify_url' => $this->notify_url, 'amount' => array('total' => intval($order['amount']), 'currency' => 'CNY'));

		$api = SDK_WEIXIN_PAY_V3_TRANSACTIONS_NATIVE;
		$res = $this->v3_wechat_request_json($api, json_encode($data));
		$res = json_decode($res, true);
		if($res['code_url']) {
			return array('code' => 200, 'url' => $res['code_url']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function v3_wechat_h5_pay($order) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}
		$data = array('appid' => $this->settings['appid'], 'mchid' => $this->settings['mch_id'], 'description' => $order['subject'] . ': ' . $order['description'], 'out_trade_no' => $order['out_biz_no'], 'notify_url' => $this->notify_url, 'amount' => array('total' => intval($order['amount']), 'currency' => 'CNY'), 'scene_info' => array('payer_client_ip' => $_G['clientip'], 'h5_info' => array('type' => checkmobile())));

		$api = SDK_WEIXIN_PAY_V3_TRANSACTIONS_H5;
		$res = $this->v3_wechat_request_json($api, json_encode($data));
		$res = json_decode($res, true);
		if($res['h5_url']) {
			return array('code' => 200, 'url' => $res['h5_url']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function v3_wechat_h5_jsapi($order, $openid) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$order['subject'] = diconv($order['subject'], $_G['charset'], 'UTF-8');
			$order['description'] = diconv($order['description'], $_G['charset'], 'UTF-8');
		}
		$data = array('appid' => $this->settings['appid'], 'mchid' => $this->settings['mch_id'], 'description' => $order['subject'] . ': ' . $order['description'], 'out_trade_no' => $order['out_biz_no'], 'notify_url' => $this->notify_url, 'amount' => array('total' => intval($order['amount']), 'currency' => 'CNY'), 'payer' => array('openid' => $openid));

		$api = SDK_WEIXIN_PAY_V3_TRANSACTIONS_H5;
		$res = $this->v3_wechat_request_json($api, json_encode($data));
		$res = json_decode($res, true);
		if($res['prepay_id']) {
			return array('code' => 200, 'url' => $res['prepay_id']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function v3_wechat_query_order($out_biz_no) {
		global $_G;
		$api = SDK_WEIXIN_PAY_V3_TRANSACTIONS_OUTTRADENO;
		$res = $this->v3_wechat_request_json($api . $out_biz_no . '?mchid=' . $this->settings['mch_id'], '', 'GET');
		$res = json_decode($res, true);
		if($res['trade_state'] && $res['trade_state'] == 'SUCCESS') {
			$pay_time = strtotime($res['success_time']);
			return array('code' => 200, 'data' => array('trade_no' => $res['transaction_id'], 'payment_time' => $pay_time));
		} elseif($res['trade_state']) {
			return array('code' => $res['trade_state'], 'message' => $res['trade_state_desc']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function v3_wechat_refund($refund_no, $trade_no, $total_amount, $refund_amount, $refund_desc) {
		global $_G;
		if(strtoupper($_G['charset'] != 'UTF-8')) {
			$refund_desc = diconv($refund_desc, $_G['charset'], 'UTF-8');
		}
		$data = array('transaction_id' => $trade_no, 'out_refund_no' => $refund_no, 'reason' => $refund_desc, 'amount' => array('refund' => intval($refund_amount), 'total' => intval($total_amount), 'currency' => 'CNY'));

		$api = SDK_WEIXIN_PAY_V3_REFUND_DOMESTIC_REFUNDS;
		$res = $this->v3_wechat_request_json($api, json_encode($data));
		$res = json_decode($res, true);
		if($res['status'] == 'SUCCESS') {
			return array('code' => 200, 'data' => array('refund_time' => strtotime($res['success_time'])));
		} elseif($res['status']) {
			return array('code' => 201, 'message' => $res['status']);
		} elseif($res['status']) {
			return array('code' => 500, 'message' => $res['status']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function v3_wechat_refund_query($refund_no) {
		global $_G;
		$api = SDK_WEIXIN_PAY_V3_REFUND_DOMESTIC_REFUNDS_QUERY;
		$res = $this->v3_wechat_request_json($api . $refund_no, '', 'GET');
		$res = json_decode($res, true);
		if($res['status'] == 'SUCCESS') {
			return array('code' => 200, 'data' => array('refund_time' => strtotime($res['success_time'])));
		} elseif($res['status']) {
			return array('code' => 201, 'message' => $res['status']);
		} elseif($res['status']) {
			return array('code' => 500, 'message' => $res['status']);
		} else {
			if(strtoupper($_G['charset'] != 'UTF-8') && $res['message']) {
				$res['message'] = diconv($res['message'], 'UTF-8', $_G['charset']);
			}
			return array('code' => $res['code'], 'message' => $res['message']);
		}
	}

	private function wechat_o2x($data) {
		$xml = '<xml>';
		foreach($data as $key => $value) {
			$xml .= "\n<{$key}>{$value}</{$key}>";
		}
		$xml .= "\n</xml>";
		return $xml;
	}

	private function wechat_x2o($xml) {
		if(function_exists('libxml_disable_entity_loader')) {
			libxml_disable_entity_loader(true);
		}
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $data;
	}

	private function wechat_sign($token, $data, $sign = 0) {
		ksort($data);
		$signstr = '';
		foreach($data as $key => $value) {
			if(!$value || ($sign && $key == 'sign')) {
				continue;
			}
			$signstr .= $key . '=' . $value . '&';
		}
		$signstr .= 'key=' . $token;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}

	private function wechat_nonce() {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for($i = 0; $i < 32; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	private function v3_wechat_jsapi_authorization($data) {
		$message = $data['appId'] . "\n" . $data['timeStamp'] . "\n" . $data['nonceStr'] . "\n" . $data['package'];
		openssl_sign($message, $sign, $this->settings['v3_private_key'], 'sha256WithRSAEncryption');
		$sign = base64_encode($sign);
		return $sign;
	}

	private function v3_wechat_authorization($api, $method, $json) {
		$url_values = parse_url($api);
		$timestamp = time();
		$nonce = $this->wechat_nonce();
		$message = $method . "\n" . $url_values['path'] . ($url_values['query'] ? ('?' . $url_values['query']) : '') . "\n" . $timestamp . "\n" . $nonce . "\n" . $json . "\n";
		openssl_sign($message, $sign, $this->settings['v3_private_key'], 'sha256WithRSAEncryption');
		$sign = base64_encode($sign);
		$token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $this->settings['mch_id'], $nonce, $timestamp, $this->settings['v3_serial_no'], $sign);
		return $token;
	}

	private function v3_wechat_decrypt2string($associateddata, $noncestr, $ciphertext) {
		$ciphertext = base64_decode($ciphertext);
		if(strlen($ciphertext) <= 16) {
			return false;
		}

		// ext-sodium (default installed on >= PHP 7.2)
		if(function_exists('sodium_crypto_aead_aes256gcm_is_available') && sodium_crypto_aead_aes256gcm_is_available()) {
			return sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associateddata, $noncestr, $this->settings['v3_key']);
		}
		// openssl (PHP >= 7.1 support AEAD)
		if(PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
			$ctext = substr($ciphertext, 0, -16);
			$authTag = substr($ciphertext, -16);

			return openssl_decrypt($ctext, 'aes-256-gcm', $this->settings['v3_key'], OPENSSL_RAW_DATA, $noncestr, $authTag, $associateddata);
		}

		return false;
	}

	private function wechat_request($api, $data = null) {
		$client = filesock::open(array(
			'url' => $api,
			'method' => 'POST',
			'post' => $data
		));
		return $client->request();
	}

	private function wechat_request_xml($api, $xml, $cert = false) {
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

	private function v3_wechat_request_json($api, $json = '', $method = 'POST') {
		$client = filesock::open(array(
			'url' => $api,
			'method' => $method,
			'rawdata' => $json,
			'encodetype' => 'JSON',
			'header' => array(
				'Accept' => 'application/json',
				'Authorization' => 'WECHATPAY2-SHA256-RSA2048 ' . $this->v3_wechat_authorization($api, $method, $json)
			)
		));
		$data = $client->request();
		if(!$data) {
			$data = $client->filesockbody;
		}
		return $data;
	}

	private function wechat_device() {
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if(strpos($useragent, 'MicroMessenger') !== false) {
			return 'wechat';
		} else {
			return checkmobile();
		}
	}
}
