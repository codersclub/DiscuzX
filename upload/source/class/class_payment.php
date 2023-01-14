<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_payment.php 36342 2021-05-17 15:10:45Z dplugin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class payment {

	public static function enable() {

		$channels = C::t('common_setting')->fetch_all_setting(array('ec_wechat', 'ec_alipay', 'ec_qpay'), true);
		if($channels['ec_alipay']['on']) {
			return true;
		}
		if($channels['ec_wechat']['on']) {
			return true;
		}
		if($channels['ec_qpay']['on']) {
			return true;
		}
		return false;
	}

	public static function channels() {
		$result = array();
		$result['qpay'] = array(
			'id' => 'qpay',
			'title' => lang('spacecp', 'payment_qpay'),
			'logo' => 'static/image/common/qpay_logo.svg',
			'enable' => 0
		);
		$result['wechat'] = array(
			'id' => 'wechat',
			'title' => lang('spacecp', 'payment_wechat'),
			'logo' => 'static/image/common/wechatpay_logo.svg',
			'enable' => 0
		);
		$result['alipay'] = array(
			'id' => 'alipay',
			'title' => lang('spacecp', 'payment_alipay'),
			'logo' => 'static/image/common/alipay_logo.svg',
			'enable' => 0
		);

		$channels = C::t('common_setting')->fetch_all_setting(array('ec_wechat', 'ec_alipay', 'ec_qpay'), true);
		if($channels['ec_alipay']['on']) {
			$result['alipay']['enable'] = 1;
		}
		if($channels['ec_wechat']['on']) {
			$result['wechat']['enable'] = 1;
		}
		if($channels['ec_qpay']['on']) {
			$result['qpay']['enable'] = 1;
		}
		return $result;
	}

	public static function get($channel) {
		$sdk_class = DISCUZ_ROOT . './api/payment/payment_' . $channel . '.php';
		if(!file_exists($sdk_class)) {
			return false;
		}
		require_once $sdk_class;
		$classname = 'payment_' . $channel;
		if(!class_exists($classname)) {
			return false;
		}
		return new $classname();
	}

	public static function create_order($type, $subject, $description, $amount, $return_url, $params = null, $fee = 0, $expire = 3600) {
		global $_G;

		if(strpos($type, ':') !== false) {
			$type_values = explode(':', $type);
			$type_name = lang('plugin/' . $type_values[0], $type_values[1]);
		} else {
			$type_name = lang('payment/type', $type);
		}

		$out_biz_no = dgmdate(TIMESTAMP, 'YmdHis') . random(14, 1);
		$data = array(
			'out_biz_no' => $out_biz_no,
			'type' => $type,
			'type_name' => $type_name,
			'uid' => $_G['uid'],
			'amount' => $amount,
			'amount_fee' => $fee,
			'subject' => $subject,
			'description' => $description,
			'expire_time' => time() + $expire,
			'status' => 0,
			'return_url' => str_replace(array('{out_biz_no}'), array($out_biz_no), $return_url),
			'clientip' => $_G['clientip'],
			'remoteport' => $_G['remoteport'],
			'dateline' => time()
		);
		if($params) {
			$data['data'] = serialize($params);
		}
		$id = C::t('common_payment_order')->insert($data, true);
		return $_G['siteurl'] . 'home.php?mod=spacecp&ac=payment&op=pay&order_id=' . $id;
	}

	public static function finish_order($channel, $out_biz_no, $trade_no, $payment_time) {
		$order = C::t('common_payment_order')->fetch_by_biz_no($out_biz_no);
		if(!$order || $order['status']) {
			if(!$order) {
				$error = 50002;
			} else {
				$error = 50003;
			}
			self::paymentlog($channel, 0, 0, 0, $error, array('out_biz_no' => $out_biz_no, 'trade_no' => $trade_no));
			return true;
		}

		$order['trade_no'] = $trade_no;
		$order['payment_time'] = $payment_time;
		$order['channel'] = $channel;
		$order['status'] = 1;

		$status = C::t('common_payment_order')->update_order_finish($order['id'], $order['trade_no'], $order['payment_time'], $order['channel']);
		if($status) {
			self::retry_callback_order($order);
		} else {
			self::paymentlog($channel, 0, $order['uid'], $order['id'], 50004, array('out_biz_no' => $out_biz_no, 'trade_no' => $trade_no));
		}
		return true;
	}

	public static function retry_callback_order($order) {
		if($order['status'] != 1) {
			return array('code' => 500, 'message' => lang('message', 'payment_retry_callback_no_pay'));
		}
		if(!$order['callback_status']) {
			$order_type = $order['type'];
			if(strpos($order_type, ':') !== false) {
				$order_type_values = explode(':', $order_type);
				$callback_class = DISCUZ_ROOT . './source/plugin/' . $order_type_values[0] . '/payment/' . $order_type_values[1] . '.php';
				$class_name = $order_type_values[1];
			} else {
				$callback_class = DISCUZ_ROOT . './source/class/payment/' . $order_type . '.php';
				$class_name = $order_type;
			}
			if(file_exists($callback_class)) {
				require_once $callback_class;
				if(class_exists($class_name)) {
					$callback = new $class_name();
					$callback->callback(dunserialize($order['data']), $order);
				}
			}
			C::t('common_payment_order')->update($order['id'], array('callback_status' => 1));
		}
		return array('code' => 200);
	}

	public static function query_order($channel, $order_id) {
		$order = C::t('common_payment_order')->fetch($order_id);
		if(!$order) {
			return array('code' => 500, 'message' => lang('message', 'payment_order_no_exist'));
		}
		$payment = payment::get($channel);
		if(!$payment) {
			return array('code' => 500, 'message' => lang('message', 'payment_type_no_exist'));
		}
		$result = $payment->status($order['out_biz_no']);
		if($result['code'] == 200 && $order['status'] != 1 && $result['data']) {
			payment::finish_order($channel, $order['out_biz_no'], $result['data']['trade_no'], $result['data']['payment_time']);
		}
		return $result;
	}

	public static function refund($refund_no, $order_id, $amount, $refund_desc) {
		global $_G;
		$order = C::t('common_payment_order')->fetch($order_id);
		if(!$order || $order['status'] != 1) {
			return array('code' => 500, 'message' => lang('message', 'payment_order_no_exist'));
		}

		$refund_order = C::t('common_payment_refund')->fetch_by_no($refund_no);
		if($refund_order) {
			if($refund_order['order_id'] != $order_id) {
				return array('code' => 500, 'message' => lang('message', 'payment_refund_id_exist'));
			}
			if($refund_order['status'] == 2) {
				return array('code' => 200, 'data' => array(
					'refund_time' => $refund_order['refund_time']
				));
			}
			if($refund_order['status'] == 1) {
				return array('code' => 500, 'message' => lang('message', 'payment_refund_exist'));
			}

			C::t('common_payment_refund')->update_refund_by_no($refund_no, array(
				'amount' => $amount,
				'description' => $refund_desc,
				'clientip' => $_G['clientip'],
				'remoteport' => $_G['remoteport'],
				'status' => 1,
				'dateline' => time()
			));
		} else {
			C::t('common_payment_refund')->insert(array(
				'order_id' => $order_id,
				'out_biz_no' => $refund_no,
				'amount' => $amount,
				'description' => $refund_desc,
				'status' => 1,
				'clientip' => $_G['clientip'],
				'remoteport' => $_G['remoteport'],
				'dateline' => time()
			));
		}

		$payment = payment::get($order['channel']);
		$result = $payment->refund($refund_no, $order['trade_no'], $order['amount'], $amount, $refund_desc);
		if($result['code'] == 200) {
			C::t('common_payment_refund')->update_refund_by_no($refund_no, array(
				'status' => 2,
				'refund_time' => $result['data']['refund_time']
			));
		} else {
			C::t('common_payment_refund')->update_refund_by_no($refund_no, array(
				'status' => 2,
				'error' => $result['message']
			));
		}
		return $result;
	}

	public static function refund_status($refund_no, $order_id) {
		$order = C::t('common_payment_order')->fetch($order_id);
		if(!$order || $order['status'] != 1) {
			return array('code' => 500, 'message' => lang('message', 'payment_order_no_exist'));
		}
		$refund_order = C::t('common_payment_refund')->fetch_by_no($refund_no);
		if($refund_order) {
			if($refund_order['order_id'] != $order_id) {
				return array('code' => 500, 'message' => lang('message', 'payment_refund_id_exist'));
			} elseif($refund_order['status'] == 1) {
				return array('code' => 200, 'data' => array('refund_time' => $refund_order['refund_time']));
			}
		}

		$payment = payment::get($order['channel']);
		$result = $payment->refund_status($refund_no, $order['trade_no']);
		if($result['code'] == 200) {
			C::t('common_payment_refund')->update_refund_by_no($refund_no, array(
				'status' => 2,
				'refund_time' => $result['data']['refund_time']
			));
		} else {
			C::t('common_payment_refund')->update_refund_by_no($refund_no, array(
				'status' => 2,
				'error' => $result['message']
			));
		}
		return $result;
	}

	public static function transfer($channel, $transfer_no, $amount, $uid, $realname, $account, $title = '', $desc = '') {
		global $_G;
		$transfer_order = C::t('common_payment_transfer')->fetch_by_no($transfer_no);
		if($transfer_order) {
			if($transfer_order['channel'] != $channel || $transfer_order['amount'] != $amount || $transfer_order['account'] != $account) {
				return array('code' => 500, 'message' => lang('message', 'payment_transfer_id_exist'));
			}
			if($transfer_order['status'] == 2) {
				return array('code' => 200, 'data' => array(
					'transfer_time' => $transfer_order['trade_time']
				));
			}
			if($transfer_order['status'] == 1) {
				return array('code' => 500, 'message' => lang('message', 'payment_transfer_exist'));
			}

			C::t('common_payment_transfer')->update_transfer_by_no($transfer_no, array(
				'subject' => $title,
				'description' => $desc,
				'realname' => $realname,
				'clientip' => $_G['clientip'],
				'remoteport' => $_G['remoteport'],
				'uid' => $uid,
				'status' => 1,
				'dateline' => time()
			));
		} else {
			C::t('common_payment_transfer')->insert(array(
				'out_biz_no' => $transfer_no,
				'amount' => $amount,
				'subject' => $title,
				'description' => $desc,
				'realname' => $realname,
				'account' => $account,
				'channel' => $channel,
				'uid' => $uid,
				'status' => 1,
				'clientip' => $_G['clientip'],
				'remoteport' => $_G['remoteport'],
				'dateline' => time()
			));
		}

		$payment = payment::get($channel);
		$result = $payment->transfer($transfer_no, $amount, $realname, $account, $title, $desc);
		if($result['code'] == 200) {
			C::t('common_payment_transfer')->update_transfer_by_no($transfer_no, array(
				'status' => 2,
				'trade_time' => $result['data']['transfer_time']
			));
		} else {
			C::t('common_payment_transfer')->update_transfer_by_no($transfer_no, array(
				'status' => 3,
				'error' => $result['message']
			));
		}
		return $result;
	}

	public static function transfer_status($transfer_no) {
		$refund_order = C::t('common_payment_transfer')->fetch_by_no($transfer_no);
		if(!$refund_order) {
			return array('code' => 500, 'message' => lang('message', 'payment_transfer_id_no_exist'));
		} elseif($refund_order['status'] == 2) {
			return array('code' => 200, 'data' => array('transfer_time' => $refund_order['trade_time']));
		}

		$payment = payment::get($refund_order['channel']);
		$result = $payment->transfer_status($transfer_no);
		if($result['code'] == 200) {
			C::t('common_payment_transfer')->update_transfer_by_no($transfer_no, array(
				'status' => 2,
				'trade_time' => $result['data']['transfer_time']
			));
		} else {
			C::t('common_payment_transfer')->update_transfer_by_no($transfer_no, array(
				'status' => 3,
				'error' => $result['message']
			));
		}
		return $result;
	}

	public static function paymentlog($channel, $status, $uid, $order_id, $error, $data) {
		global $_G;
		require_once libfile('function/misc');

		writelog('pmtlog', implode("\t", clearlogstring(array(
			$_G['timestamp'],
			$channel,
			$status,
			$order_id,
			$uid,
			$_G['clientip'],
			$_G['remoteport'],
			$error,
			is_array($data) ? json_encode($data) : $data
		))));
	}
}
