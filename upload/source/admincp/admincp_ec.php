<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_ec.php 30969 2012-07-04 10:18:10Z monkey $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
if(!defined('APPTYPEID')) {
	define('APPTYPEID', 2);
}
$checktype = $_GET['checktype'];
cpheader();

if($operation == 'alipay') {

	$alipaysettings = C::t('common_setting')->fetch_setting('ec_alipay', true);

	if(!empty($checktype)) {
		if($checktype == 'credit') {
			$return_url = $_G['siteurl'] . 'home.php?mod=spacecp&ac=credit';
			$pay_url = payment::create_order('payment_credit', $lang['ec_alipay_checklink_credit'], $lang['ec_alipay_checklink_credit'], 1, $return_url);
			ob_end_clean();
			dheader('location: ' . $pay_url);
		}
		exit;
	}

	if(!submitcheck('alipaysubmit')) {

		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 0),
			array('nav_ec_wechat', 'ec&operation=wechat', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 1),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0),
		));

		/*search={"nav_ec":"action=setting&operation=ec","nav_ec_alipay":"action=ec&operation=alipay"}*/
		showtips('ec_alipay_tips');
		showformheader('ec&operation=alipay');

		showtableheader('', 'nobottom');
		showtitle('ec_alipay');

		showtagheader('tbody', 'alipay_setting', true);
		showsetting('ec_alipay_on', 'settingsnew[on]', $alipaysettings['on'], 'radio');
		$check = array();
		$alipaysettings['ec_alipay_sign_mode'] ? $check['true'] = "checked" : $check['false'] = "checked";
		$alipaysettings['ec_alipay_sign_mode'] ? $check['false'] = '' : $check['true'] = '';
		$check['hidden1'] = ' onclick="$(\'sign_model_01\').style.display = \'none\';$(\'sign_model_02\').style.display = \'\';"';
		$check['hidden0'] = ' onclick="$(\'sign_model_01\').style.display = \'\';$(\'sign_model_02\').style.display = \'none\';"';
		$html = '<ul onmouseover="altStyle(this);">' .
			'<li' . ($check['false'] ? ' class="checked"' : '') . '><input class="radio" type="radio" name="settingsnew[ec_alipay_sign_mode]" value="0" ' . $check['false'] . $check['hidden0'] . '>&nbsp;' . lang('admincp', 'ec_alipay_sign_mode_01') . '</li>' .
			'<li' . ($check['true'] ? ' class="checked"' : '') . '><input class="radio" type="radio" name="settingsnew[ec_alipay_sign_mode]" value="1" ' . $check['true'] . $check['hidden1'] . '>&nbsp;' . lang('admincp', 'ec_alipay_sign_mode_02') . '</li>' .
			'</ul>';
		showsetting('ec_alipay_sign_mode', '', '', $html);
		showtagfooter('tbody');

		showtagheader('tbody', 'sign_model_01', !$alipaysettings['ec_alipay_sign_mode']);
		showsetting('ec_alipay_appid', 'settingsnew[mode_a_appid]', $alipaysettings['mode_a_appid'], 'text');
		$alipay_securitycodemask = $alipaysettings['mode_a_app_private_key'] ? substr($alipaysettings['mode_a_app_private_key'], 0, 40) . '********' . substr($alipaysettings['mode_a_app_private_key'], -40) : '';
		showsetting('ec_alipay_app_private_key', 'settingsnew[mode_a_app_private_key]', $alipay_securitycodemask, 'textarea');
		$alipay_securitycodemask = $alipaysettings['mode_a_alipay_public_key'] ? substr($alipaysettings['mode_a_alipay_public_key'], 0, 40) . '********' . substr($alipaysettings['mode_a_alipay_public_key'], -40) : '';
		showsetting('ec_alipay_public_key', 'settingsnew[mode_a_alipay_public_key]', $alipay_securitycodemask, 'textarea');
		showtagfooter('tbody');

		showtagheader('tbody', 'sign_model_02', $alipaysettings['ec_alipay_sign_mode']);
		showsetting('ec_alipay_appid', 'settingsnew[mode_b_appid]', $alipaysettings['mode_b_appid'], 'text');
		$alipay_securitycodemask = $alipaysettings['mode_b_app_private_key'] ? $alipaysettings['mode_b_app_private_key'][0] . '********' . substr($alipaysettings['mode_b_app_private_key'], -4) : '';
		showsetting('ec_alipay_app_private_key', 'settingsnew[mode_b_app_private_key]', $alipay_securitycodemask, 'textarea', '', 0, lang('admincp', 'ec_alipay_app_private_key_b_comment'));
		$alipay_securitycodemask = $alipaysettings['mode_b_app_cert'] ? substr($alipaysettings['mode_b_app_cert'], 0, 40) . '********' . substr($alipaysettings['mode_b_app_cert'], -40) : '';
		showsetting('ec_alipay_app_cert', 'settingsnew[mode_b_app_cert]', $alipay_securitycodemask, 'textarea');
		$alipay_securitycodemask = $alipaysettings['mode_b_alipay_cert'] ? substr($alipaysettings['mode_b_alipay_cert'], 0, 40) . '********' . substr($alipaysettings['mode_b_alipay_cert'], -40) : '';
		showsetting('ec_alipay_alipay_cert', 'settingsnew[mode_b_alipay_cert]', $alipay_securitycodemask, 'textarea');
		$alipay_securitycodemask = $alipaysettings['mode_b_alipay_root_cert'] ? substr($alipaysettings['mode_b_alipay_root_cert'], 0, 40) . '********' . substr($alipaysettings['mode_b_alipay_root_cert'], -40) : '';
		showsetting('ec_alipay_alipay_root_cert', 'settingsnew[mode_b_alipay_root_cert]', $alipay_securitycodemask, 'textarea');
		showtagfooter('tbody');

		showsetting('ec_alipay_check', '', '',
			'<a href="' . ADMINSCRIPT . '?action=ec&operation=alipay&checktype=credit" target="_blank">' . $lang['ec_alipay_checklink_credit'] . '</a><br />'
		);
		/*search*/
		showtableheader('', 'notop');
		showsubmit('alipaysubmit');
		showtablefooter();
		showformfooter();

	} else {
		$settingsnew = $_GET['settingsnew'];
		foreach($settingsnew as $name => $value) {
			if($value == $alipaysettings[$name] || strpos($value, '********') !== false) {
				continue;
			}
			$value = daddslashes($value);
			$alipaysettings[$name] = $value;
		}
		C::t('common_setting')->update_setting('ec_alipay', $alipaysettings);
		updatecache('setting');

		cpmsg('alipay_succeed', 'action=ec&operation=alipay', 'succeed');
	}

} elseif($operation == 'wechat') {

	$wechatsettings = C::t('common_setting')->fetch_setting('ec_wechat', true);
	if(!empty($checktype)) {
		if($checktype == 'credit') {
			$return_url = $_G['siteurl'] . 'home.php?mod=spacecp&ac=credit';
			$pay_url = payment::create_order('payment_credit', $lang['ec_alipay_checklink_credit'], $lang['ec_alipay_checklink_credit'], 1, $return_url);
			ob_end_clean();
			dheader('location: ' . $pay_url);
		}
		exit;
	}

	if(!submitcheck('wechatsubmit')) {

		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 0),
			array('nav_ec_wechat', 'ec&operation=wechat', 1),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
		));

		/*search={"nav_ec":"action=setting&operation=ec","nav_ec_wechat":"action=ec&operation=wechat"}*/
		showtips('ec_wechat_tips');
		showformheader('ec&operation=wechat');

		showtableheader('', 'nobottom');
		showtitle('ec_wechat');
		showtagheader('tbody', 'alipay_wechat', true);
		showsetting('ec_wechat_on', 'settingsnew[on]', $wechatsettings['on'], 'radio');

		$wxpayment = payment::get('wechat');
		$check = array();
		$wechatsettings['ec_wechat_version'] ? $check['true'] = "checked" : $check['false'] = "checked";
		$wechatsettings['ec_wechat_version'] ? $check['false'] = '' : $check['true'] = '';
		$check['hidden1'] = ' onclick="$(\'api_version_2\').style.display = \'none\';$(\'api_version_3\').style.display = \'\';"';
		$check['hidden0'] = ' onclick="$(\'api_version_2\').style.display = \'\';$(\'api_version_3\').style.display = \'none\';"';
		$html = '<ul onmouseover="altStyle(this);"><li' . ($check['false'] ? ' class="checked"' : '') . '><input class="radio" type="radio" name="settingsnew[ec_wechat_version]" value="0" ' . $check['false'] . $check['hidden0'] . '>&nbsp;' . $lang['ec_wechat_version_2'] . '</li>';
		if($wxpayment->v3_wechat_support()) {
			$html .= '<li' . ($check['true'] ? ' class="checked"' : '') . '><input class="radio" type="radio" name="settingsnew[ec_wechat_version]" value="1" ' . $check['true'] . $check['hidden1'] . '>&nbsp;' . $lang['ec_wechat_version_3'] . '</li>';
		} else {
			$html .= '<li style="margin-left: 5px; color: red;">' . $lang['ec_wechat_version_3'] . '(' . $lang['ec_wechat_php_version_low'] . ')</li>';
		}
		$html .= '</ul>';
		showsetting('ec_wechat_version', '', '', $html);
		showsetting('ec_wechat_appid', 'settingsnew[appid]', $wechatsettings['appid'], 'text');
		$wechat_securitycodemask = $wechatsettings['appsecret'] ? $wechatsettings['appsecret'][0] . '********' . substr($wechatsettings['appsecret'], -4) : '';
		showsetting('ec_wechat_appsecret', 'settingsnew[appsecret]', $wechat_securitycodemask, 'text');
		showsetting('ec_wechat_mch_id', 'settingsnew[mch_id]', $wechatsettings['mch_id'], 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'api_version_2', !$wechatsettings['ec_wechat_version']);
		$wechat_securitycodemask = $wechatsettings['v1_key'] ? $wechatsettings['v1_key'][0] . '********' . substr($wechatsettings['v1_key'], -4) : '';
		showsetting('ec_wechat_v1_key', 'settingsnew[v1_key]', $wechat_securitycodemask, 'text');
		showsetting('ec_wechat_v1_cert', 'settingsnew[v1_cert_path]', $wechatsettings['v1_cert_path'], 'text', '', 0, lang('admincp', 'ec_wechat_v1_cert_comment', array('randomstr' => random(10))));
		showtagfooter('tbody');

		showtagheader('tbody', 'api_version_3', $wechatsettings['ec_wechat_version']);
		$wechat_securitycodemask = $wechatsettings['v3_key'] ? $wechatsettings['v3_key'][0] . '********' . substr($wechatsettings['v3_key'], -4) : '';
		showsetting('ec_wechat_v3_key', 'settingsnew[v3_key]', $wechat_securitycodemask, 'text');
		$wechat_securitycodemask = $wechatsettings['v3_private_key'] ? substr($wechatsettings['v3_private_key'], 0, 40) . '********' . substr($wechatsettings['v3_private_key'], -40) : '';
		showsetting('ec_wechat_v3_private_key', 'settingsnew[v3_private_key]', $wechat_securitycodemask, 'textarea');
		$wechat_securitycodemask = $wechatsettings['v3_serial_no'] ? $wechatsettings['v3_serial_no'][0] . '********' . substr($wechatsettings['v3_serial_no'], -4) : '';
		showsetting('ec_wechat_v3_serial_no', 'settingsnew[v3_serial_no]', $wechat_securitycodemask, 'text');
		showtagfooter('tbody');

		showsetting('ec_wechat_check', '', '',
			'<a href="' . ADMINSCRIPT . '?action=ec&operation=wechat&checktype=credit" target="_blank">' . $lang['ec_wechat_checklink_credit'] . '</a><br />'
		);
		/*search*/
		showtableheader('', 'notop');
		showsubmit('wechatsubmit');
		showtablefooter();
		showformfooter();

	} else {
		$settingsnew = $_GET['settingsnew'];
		foreach($settingsnew as $name => $value) {
			if($value == $wechatsettings[$name] || strpos($value, '********') !== false) {
				continue;
			}
			$value = daddslashes($value);
			$wechatsettings[$name] = $value;
		}
		C::t('common_setting')->update_setting('ec_wechat', $wechatsettings);
		updatecache('setting');

		if($wechatsettings['ec_wechat_version'] && $wechatsettings['appid'] && $wechatsettings['mch_id'] && $wechatsettings['v3_key'] && $wechatsettings['v3_private_key'] && $wechatsettings['v3_serial_no']) {
			$payment = payment::get('wechat');
			$result = $payment->v3_wechat_certificates();
			if($result['code'] == 200) {
				$wechatsettings['v3_certificates'] = $result['data'];
			}
			C::t('common_setting')->update_setting('ec_wechat', $wechatsettings);
			updatecache('setting');
		}

		cpmsg('wechat_succeed', 'action=ec&operation=wechat', 'succeed');
	}

} elseif($operation == 'qpay') {

	$qpaysettings = C::t('common_setting')->fetch_setting('ec_qpay', true);
	if(!empty($checktype)) {
		if($checktype == 'credit') {
			$return_url = $_G['siteurl'] . 'home.php?mod=spacecp&ac=credit';
			$pay_url = payment::create_order('payment_credit', $lang['ec_alipay_checklink_credit'], $lang['ec_alipay_checklink_credit'], 1, $return_url);
			ob_end_clean();
			dheader('location: ' . $pay_url);
		}
		exit;
	}

	if(!submitcheck('qpaysubmit')) {

		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 1),
			array('nav_ec_wechat', 'ec&operation=wechat', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
		));

		/*search={"nav_ec":"action=setting&operation=ec","nav_ec_qpay":"action=ec&operation=qpay"}*/
		showtips('ec_qpay_tips');
		showformheader('ec&operation=qpay');

		showtableheader('', 'nobottom');
		showtitle('ec_qpay');
		showtagheader('tbody', 'alipay_wechat', true);
		showsetting('ec_qpay_on', 'settingsnew[on]', $qpaysettings['on'], 'radio');
		showsetting('ec_qpay_jsapi', 'settingsnew[jsapi]', $qpaysettings['jsapi'], 'radio');

		showsetting('ec_qpay_appid', 'settingsnew[appid]', $qpaysettings['appid'], 'text');
		showsetting('ec_qpay_mch_id', 'settingsnew[mch_id]', $qpaysettings['mch_id'], 'text');
		showsetting('ec_qpay_op_user_id', 'settingsnew[op_user_id]', $qpaysettings['op_user_id'], 'text');
		$qpay_securitycodemask = $qpaysettings['op_user_passwd'] ? $qpaysettings['op_user_passwd'][0] . '********' . substr($qpaysettings['op_user_passwd'], -4) : '';
		showsetting('ec_qpay_op_user_passwd', 'settingsnew[op_user_passwd]', $qpay_securitycodemask, 'text');
		showtagfooter('tbody');

		showtagheader('tbody', 'api_version_2', true);
		$qpay_securitycodemask = $qpaysettings['v1_key'] ? $qpaysettings['v1_key'][0] . '********' . substr($qpaysettings['v1_key'], -4) : '';
		showsetting('ec_qpay_v1_key', 'settingsnew[v1_key]', $qpay_securitycodemask, 'text');
		showsetting('ec_qpay_v1_cert', 'settingsnew[v1_cert_path]', $qpaysettings['v1_cert_path'], 'text', '', 0, lang('admincp', 'ec_qpay_v1_cert_comment', array('randomstr' => random(10))));
		showtagfooter('tbody');

		showsetting('ec_qpay_check', '', '',
			'<a href="' . ADMINSCRIPT . '?action=ec&operation=qpay&checktype=credit" target="_blank">' . $lang['ec_qpay_checklink_credit'] . '</a><br />'
		);
		/*search*/
		showtableheader('', 'notop');
		showsubmit('qpaysubmit');
		showtablefooter();
		showformfooter();

	} else {
		$settingsnew = $_GET['settingsnew'];
		foreach($settingsnew as $name => $value) {
			if($value == $qpaysettings[$name] || strpos($value, '********') !== false) {
				continue;
			}
			$value = daddslashes($value);
			if($name == 'op_user_passwd') {
				$value = md5($value);
			}
			$qpaysettings[$name] = $value;
		}
		C::t('common_setting')->update_setting('ec_qpay', $qpaysettings);
		updatecache('setting');

		cpmsg('qpay_succeed', 'action=ec&operation=qpay', 'succeed');
	}

} elseif($operation == 'paymentorders') {

	shownav('extended', 'nav_ec');
	showsubmenu('nav_ec', array(
		array('nav_ec_config', 'setting&operation=ec', 0),
		array('nav_ec_qpay', 'ec&operation=qpay', 0),
		array('nav_ec_wechat', 'ec&operation=wechat', 0),
		array('nav_ec_alipay', 'ec&operation=alipay', 0),
		array('nav_ec_credit', 'ec&operation=credit', 0),
		array('nav_ec_orders', 'ec&operation=orders', 0),
		array('nav_ec_tradelog', 'tradelog', 0),
		array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
		array('nav_ec_paymentorders', 'ec&operation=paymentorders', 1),
		array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
	));

	if(submitcheck('querysubmit')) {
		$order_id = intval($_GET['order_id']);
		$channel = daddslashes($_GET['channel']);

		$result = payment::query_order($channel, $order_id);
		if($result['code'] == 200) {
			cpmsg('payment_succeed', $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=paymentorders', 'succeed');
		} else {
			cpmsg($result['message'], $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=paymentorders', 'error');
		}
	} elseif($_GET['op'] == 'retry') {
		$order_id = intval($_GET['order_id']);
		$order = C::t('common_payment_order')->fetch($order_id);
		$result = payment::retry_callback_order($order);
		if($result['code'] == 200) {
			cpmsg('payment_succeed', $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=paymentorders', 'succeed');
		} else {
			cpmsg($result['message'], $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=paymentorders', 'error');
		}
	} elseif($_GET['op'] == 'query') {
		$order_id = intval($_GET['order_id']);
		$order = C::t('common_payment_order')->fetch($order_id);

		$channels = payment::channels();

		$user = getuserbyuid($order['uid']);
		showformheader('ec&operation=paymentorders');
		showhiddenfields(array('order_id' => $order['id']));
		showtableheader('ec_paymentorders_detail');
		showsetting('ec_paymentorders_no', '', '', $order['out_biz_no']);
		showsetting('ec_paymentorders_type', '', '', $order['type_name']);
		showsetting('ec_paymentorders_desc', '', '', $order['subject'] . ($order['description'] ? '<br/>' . $order['description'] : ''));
		showsetting('ec_paymentorders_user', '', '', $user['username'] . ' (' . $order['uid'] . ')' . '<br/>' . $order['clientip'] . ':' . $order['remoteport']);
		showsetting('ec_paymentorders_amount', '', '', number_format($order['amount'] / 100, 2, '.', ','));
		showsetting('ec_orders_submitdate', '', '', dgmdate($order['dateline']));
		$channelradios = '<ul onmouseover="altStyle(this);">';
		$channelindex = 0;
		foreach($channels as $index => $channel) {
			$channelradios .= '<li'.($channelindex === 0 ? ' class="checked"' : '').'><input class="radio" type="radio" name="channel" '.($channelindex === 0 ? 'checked' : '').' value="' . $channel['id'] . '">&nbsp;' . $channel['title'] . '</li>';
			$channelindex++;
		}
		$channelradios .= '</ul>';
		showsetting('ec_paymentorders_channel', '', '', $channelradios);
		showtablefooter();
		showsubmit('querysubmit', 'ec_paymentorders_op_status', '', $lang['ec_paymentorders_query_submit_tips']);
		showtablefooter();
		showformfooter();
	} else {
		$start_limit = ($page - 1) * $_G['tpp'];

		/** search */
		echo '<style type="text/css">.order-status-0 td { color: #555; } .order-status-1 td { color: green; } .order-status-1 td a { color: #fe8080; } .order-status-2 td, .order-status-2 td a { color: #ccc; } .order-status-3 td { color: red; }</style>';
		echo '<script src="static/js/calendar.js" type="text/javascript"></script>';
		$queryparams = array(
			'out_biz_no' => daddslashes($_GET['out_biz_no']),
			'user' => daddslashes($_GET['user']),
			'type' => daddslashes($_GET['type']),
			'channel' => daddslashes($_GET['channel']),
			'status' => daddslashes($_GET['status']),
			'starttime' => daddslashes($_GET['starttime']),
			'endtime' => daddslashes($_GET['endtime']),
		);

		$types = C::t('common_payment_order')->fetch_type_all();
		$typeoptions = array();
		$typeoptions[] = '<option value="">' . $lang['all'] . '</option>';
		foreach($types as $k => $v) {
			$typeoptions[] = "<option value=\"{$k}\"" . ($k == $queryparams['type'] ? ' selected' : '') . ">{$v}</option>";
		}
		showformheader('ec&operation=paymentorders');
		showtableheader('ec_paymentorders_search');
		showtablerow('', array(
			'width="50"', 'width="200"',
			'width="50"', 'width="200"',
			'width="50"', ''
		),
			array(
				lang('admincp', 'ec_orders_search_id'), '<input type="text" class="txt" name="out_biz_no" value="' . $queryparams['out_biz_no'] . '" />',
				lang('admincp', 'ec_paymentorders_user'), '<input type="text" class="txt" name="user" value="' . $queryparams['user'] . '" />',
				lang('admincp', 'ec_paymentorders_type'), '<select name="type">' . implode('', $typeoptions) . '</select>',
			)
		);

		$channels = payment::channels();
		$channeloptions = array();
		$channeloptions[] = '<option value="">' . $lang['all'] . '</option>';
		foreach($channels as $channel) {
			$channeloptions[] = '<option value="' . $channel['id'] . '"' . ($queryparams['channel'] == $channel['id'] ? ' selected' : '') . '>' . $channel['title'] . '</option>';
		}
		$statusoptions = array();
		$statusoptions[] = '<option value="">' . $lang['all'] . '</option>';
		$statusoptions[] = '<option value="0"' . ($queryparams['status'] === '0' ? ' selected' : '') . '>' . $lang['ec_paymentorders_status_0'] . '</option>';
		$statusoptions[] = '<option value="1"' . ($queryparams['status'] === '1' ? ' selected' : '') . '>' . $lang['ec_paymentorders_status_1'] . '</option>';
		$statusoptions[] = '<option value="2"' . ($queryparams['status'] === '2' ? ' selected' : '') . '>' . $lang['ec_paymentorders_status_2'] . '</option>';
		showtablerow('', array(),
			array(
				lang('admincp', 'ec_paymentorders_channel'), '<select name="channel">' . implode('', $channeloptions) . '</select>',
				lang('admincp', 'ec_paymentorders_status'), '<select name="status">' . implode('', $statusoptions) . '</select>',
				lang('admincp', 'ec_paymentorders_date'), '<input type="text" class="txt" name="starttime" value="' . $queryparams['starttime'] . '" style="width: 108px;" onclick="showcalendar(event, this)"> - <input type="text" class="txt" name="endtime" value="' . $queryparams['endtime'] . '" style="width: 108px;" onclick="showcalendar(event, this)">',
			)
		);
		showtablefooter();
		showtableheader('', 'notop');
		showsubmit('searchsubmit');
		showtablefooter();
		/** list */
		if($queryparams['user']) {
			if(preg_match('/^\d+$/', $queryparams['user'])) {
				$queryparams['uid'] = $queryparams['user'];
			} else {
				$user = C::t('common_member')->fetch_uid_by_username($queryparams['user']);
				if($user) {
					$queryparams['uid'] = $user['uid'];
				} else {
					$queryparams['uid'] = -1;
				}
			}
		}
		$ordercount = C::t('common_payment_order')->count_by_search($queryparams['uid'], $queryparams['type'], $queryparams['starttime'], $queryparams['endtime'], $queryparams['out_biz_no'], $queryparams['channel'], $queryparams['status']);
		$multipage = multi($ordercount, $_G['tpp'], $page, ADMINSCRIPT . "?action=ec&operation=paymentorders&" . http_build_query($queryparams));

		$tdstyles = array(
			'style="width: 220px;"',
			'style="width: 100px; text-align: center"',
			'',
			'style="width: 100px;"',
			'style="width: 60px; text-align: center"',
			'style="width: 100px; text-align: right"',
			'style="width: 60px; text-align: center"',
			'style="width: 100px; text-align: right"',
			'style="width: 100px; text-align: right"',
			'style="width: 110px; text-align: right"'
		);
		showtableheader('result');
		showsubtitle(array('ec_paymentorders_no', 'ec_paymentorders_type', 'ec_paymentorders_desc', 'ec_paymentorders_buyer', 'ec_paymentorders_channel', 'ec_paymentorders_amount', 'ec_paymentorders_status', 'ec_orders_submitdate', 'ec_orders_confirmdate', ''), 'header', $tdstyles);
		if($ordercount > 0) {
			$order_list = C::t('common_payment_order')->fetch_all_by_search($queryparams['uid'], $queryparams['type'], $queryparams['starttime'], $queryparams['endtime'], $queryparams['out_biz_no'], $queryparams['channel'], $queryparams['status'], $start_limit, $_G['tpp']);
			$refund_list = C::t('common_payment_refund')->sum_by_orders(array_keys($order_list));
			foreach($order_list as $order) {
				$user = getuserbyuid($order['uid']);
				if(!$order['status'] && $order['expire_time'] < time()) {
					$order['status'] = 2;
				} elseif($order['status'] == 1 && $refund_list[$order['id']]) {
					$order['status'] = 3;
					$order['refund_amount'] = $refund_list[$order['id']]['amount'];
				}

				$amountstr = number_format($order['amount'] / 100, 2, '.', ',');
				if($order['status'] == 3) {
					$amountstr .= '<br/>' . $lang['ec_paymentorders_refund_amount'] . ': ' . number_format($order['refund_amount'] / 100, 2, '.', ',');
				}
				$operations = '';
				if(in_array($order['status'], array(0, 2))) {
					$operations .= '<a href="' . ADMINSCRIPT . '?action=ec&operation=paymentorders&op=query&order_id=' . $order['id'] . '">' . $lang['ec_paymentorders_op_status'] . '</a>';
				} elseif($order['status'] == 1 && !$order['callback_status']) {
					$operations = '<a href="' . ADMINSCRIPT . '?action=ec&operation=paymentorders&op=retry&order_id=' . $order['id'] . '">'.$lang['ec_paymentorders_callback_tips'].'</a>';
				}

				showtablerow('class="order-status-' . $order['status'] . '"', $tdstyles, array(
					$order['out_biz_no'],
					$order['type_name'],
					$order['subject'] . ($order['description'] ? '<br/>' . $order['description'] : ''),
					$user['username'] . ' (' . $order['uid'] . ')' . '<br/>' . $order['clientip'] . ':' . $order['remoteport'],
					$channels[$order['channel']]['title'],
					$amountstr,
					$lang['ec_paymentorders_status_' . $order['status']],
					dgmdate($order['dateline']),
					$order['payment_time'] ? dgmdate($order['payment_time']) : 'N/A',
					$operations
				));
			}
			showsubmit('', '', '', '', $multipage);
		} else {
			showtablerow('', array('class="center" colspan="25"'), array($lang['ec_paymentorders_no_data']));
		}
		showtablefooter();
		showformfooter();
	}

} elseif($operation == 'transferorders') {

	shownav('extended', 'nav_ec');
	showsubmenu('nav_ec', array(
		array('nav_ec_config', 'setting&operation=ec', 0),
		array('nav_ec_qpay', 'ec&operation=qpay', 0),
		array('nav_ec_wechat', 'ec&operation=wechat', 0),
		array('nav_ec_alipay', 'ec&operation=alipay', 0),
		array('nav_ec_credit', 'ec&operation=credit', 0),
		array('nav_ec_orders', 'ec&operation=orders', 0),
		array('nav_ec_tradelog', 'tradelog', 0),
		array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
		array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
		array('nav_ec_transferorders', 'ec&operation=transferorders', 1)
	));

	if($_GET['op'] == 'query') {
		$transfer_no = daddslashes($_GET['transfer_no']);

		$result = payment::transfer_status($transfer_no);
		if($result['code'] == 200) {
			cpmsg('payment_transfer_succeed', $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=transferorders&out_biz_no=' . $transfer_no, 'succeed');
		} else {
			cpmsg($result['message'], $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=transferorders&out_biz_no=' . $transfer_no, 'error');
		}
	} elseif($_GET['op'] == 'retry') {
		$order_id = intval($_GET['order_id']);
		$order = C::t('common_payment_transfer')->fetch($order_id);

		$result = payment::transfer($order['channel'], $order['out_biz_no'], $order['amount'], $order['uid'], $order['realname'], $order['account'], $order['subject'], $order['description']);
		if($result['code'] == 200) {
			cpmsg('payment_transfer_succeed', $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=transferorders&out_biz_no=' . $order['out_biz_no'], 'succeed');
		} else {
			cpmsg($result['message'], $_G['siteurl'] . ADMINSCRIPT . '?action=ec&operation=transferorders&out_biz_no=' . $order['out_biz_no'], 'error');
		}
	} else {
		$start_limit = ($page - 1) * $_G['tpp'];

		/** search */
		echo '<style type="text/css">.order-status-1 td { color: #555; } .order-status-2 td { color: green; } .order-status-3 td { color: red; }</style>';
		echo '<script src="static/js/calendar.js" type="text/javascript"></script>';
		$queryparams = array(
			'out_biz_no' => daddslashes($_GET['out_biz_no']),
			'user' => daddslashes($_GET['user']),
			'channel' => daddslashes($_GET['channel']),
			'status' => daddslashes($_GET['status']),
			'starttime' => daddslashes($_GET['starttime']),
			'endtime' => daddslashes($_GET['endtime']),
		);

		showformheader('ec&operation=transferorders');
		showtableheader('ec_transferorders_search');
		showtablerow('', array(),
			array(
				lang('admincp', 'ec_orders_search_id'), '<input type="text" class="txt" name="out_biz_no" value="' . $queryparams['out_biz_no'] . '" />',
				lang('admincp', 'ec_transferorders_user'), '<input type="text" class="txt" name="user" value="' . $queryparams['user'] . '" />',
			)
		);

		$channels = payment::channels();
		$channeloptions = array();
		$channeloptions[] = '<option value="">' . $lang['all'] . '</option>';
		foreach($channels as $channel) {
			$channeloptions[] = '<option value="' . $channel['id'] . '"' . ($queryparams['channel'] == $channel['id'] ? ' selected' : '') . '>' . $channel['title'] . '</option>';
		}
		$statusoptions = array();
		$statusoptions[] = '<option value="">' . $lang['all'] . '</option>';
		$statusoptions[] = '<option value="0"' . ($queryparams['status'] === '1' ? ' selected' : '') . '>' . $lang['ec_transferorders_status_1'] . '</option>';
		$statusoptions[] = '<option value="1"' . ($queryparams['status'] === '2' ? ' selected' : '') . '>' . $lang['ec_transferorders_status_2'] . '</option>';
		$statusoptions[] = '<option value="2"' . ($queryparams['status'] === '3' ? ' selected' : '') . '>' . $lang['ec_transferorders_status_3'] . '</option>';
		showtablerow('', array(
			'width="50"', 'width="200"',
			'width="50"', 'width="200"',
			'width="50"', ''
		),
			array(
				lang('admincp', 'ec_transferorders_channel'), '<select name="channel">' . implode('', $channeloptions) . '</select>',
				lang('admincp', 'ec_paymentorders_status'), '<select name="status">' . implode('', $statusoptions) . '</select>',
				lang('admincp', 'ec_paymentorders_date'), '<input type="text" class="txt" name="starttime" value="' . $queryparams['starttime'] . '" style="width: 108px;" onclick="showcalendar(event, this)"> - <input type="text" class="txt" name="endtime" value="' . $queryparams['endtime'] . '" style="width: 108px;" onclick="showcalendar(event, this)">',
			)
		);
		showtablefooter();
		showtableheader('', 'notop');
		showsubmit('searchsubmit');
		showtablefooter();
		/** list */
		if($queryparams['user']) {
			if(preg_match('/^\d+$/', $queryparams['user'])) {
				$queryparams['uid'] = $queryparams['user'];
			} else {
				$user = C::t('common_member')->fetch_uid_by_username($queryparams['user']);
				if($user) {
					$queryparams['uid'] = $user['uid'];
				} else {
					$queryparams['uid'] = -1;
				}
			}
		}
		$ordercount = C::t('common_payment_transfer')->count_by_search($queryparams['uid'], $queryparams['starttime'], $queryparams['endtime'], $queryparams['out_biz_no'], $queryparams['channel'], $queryparams['status']);
		$multipage = multi($ordercount, $_G['tpp'], $page, ADMINSCRIPT . "?action=ec&operation=transferorders&" . http_build_query($queryparams));

		$tdstyles = array(
			'style="width: 220px;"',
			'style="width: 100px; text-align: center"',
			'style="width: 100px; text-align: center"',
			'',
			'style="width: 100px; text-align: right"',
			'style="width: 60px; text-align: center"',
			'',
			'style="width: 100px; text-align: right"',
			'style="width: 100px; text-align: right"',
			'style="width: 25px; text-align: right"'
		);
		showtableheader('result');
		showsubtitle(array('ec_paymentorders_no', 'ec_transferorders_user', 'ec_transferorders_channel', 'ec_transferorders_desc', 'ec_paymentorders_amount', 'ec_paymentorders_status', 'ec_transferorders_error', 'ec_orders_submitdate', 'ec_orders_confirmdate', ''), 'header', $tdstyles);
		if($ordercount > 0) {
			$order_list = C::t('common_payment_transfer')->fetch_all_by_search($queryparams['uid'], $queryparams['type'], $queryparams['starttime'], $queryparams['endtime'], $queryparams['out_biz_no'], $queryparams['channel'], $queryparams['status'], $start_limit, $_G['tpp']);
			foreach($order_list as $order) {
				$user = getuserbyuid($order['uid']);
				if($order['status'] == 1) {
					$operations = '<a href="' . ADMINSCRIPT . '?action=ec&operation=transferorders&op=query&transfer_no=' . $order['out_biz_no'] . '">' . $lang['ec_paymentorders_op_status'] . '</a>';
				} elseif($order['status'] == 3) {
					$operations = '<a href="' . ADMINSCRIPT . '?action=ec&operation=transferorders&op=retry&order_id=' . $order['id'] . '">' . $lang['ec_transferorders_op_retry'] . '</a>';
				}
				showtablerow('class="order-status-' . $order['status'] . '"', $tdstyles, array(
					$order['out_biz_no'],
					$user['username'] . ' (' . $order['uid'] . ')' . '<br/>' . $order['clientip'] . ':' . $order['remoteport'],
					$channels[$order['channel']]['title'],
					$order['subject'] . ($order['description'] ? '<br/>' . $order['description'] : ''),
					number_format($order['amount'] / 100, 2, '.', ','),
					$lang['ec_transferorders_status_' . $order['status']],
					$order['status'] == 3 ? $order['error'] : '',
					dgmdate($order['dateline']),
					$order['trade_time'] ? dgmdate($order['trade_time']) : 'N/A',
					$operations
				));
			}
			showsubmit('', '', '', '', $multipage);
		} else {
			showtablerow('', array('class="center" colspan="25"'), array($lang['ec_transferorders_no_data']));
		}
		showtablefooter();
		showformfooter();
	}

} elseif($operation == 'orders') {

	$orderurl = array(
		'alipay' => 'https://www.alipay.com/trade/query_trade_detail.htm?trade_no=',
		'tenpay' => 'https://www.tenpay.com/med/tradeDetail.shtml?trans_id=',
	);

	if(!$_G['setting']['creditstrans'] || !$_G['setting']['ec_ratio']) {
		cpmsg('orders_disabled', '', 'error');
	}

	if(!submitcheck('ordersubmit')) {

		echo '<script type="text/javascript" src="' . STATICURL . 'js/calendar.js"></script>';
		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 0),
			array('nav_ec_wechat', 'ec&operation=wechat', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 1),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
		));
		/*search={"nav_ec":"action=setting&operation=ec","nav_ec_orders":"action=ec&operation=orders"}*/
		showtips('ec_orders_tips');
		showtagheader('div', 'ordersearch', !submitcheck('searchsubmit', 1));
		showformheader('ec&operation=orders');
		showtableheader('ec_orders_search');
		showsetting('ec_orders_search_status', array('orderstatus', array(
			array('', $lang['ec_orders_search_status_all']),
			array(1, $lang['ec_orders_search_status_pending']),
			array(2, $lang['ec_orders_search_status_auto_finished']),
			array(3, $lang['ec_orders_search_status_manual_finished'])
		)), intval($orderstatus), 'select');
		showsetting('ec_orders_search_id', 'orderid', $orderid, 'text');
		showsetting('ec_orders_search_users', 'users', $users, 'text');
		showsetting('ec_orders_search_buyer', 'buyer', $buyer, 'text');
		showsetting('ec_orders_search_admin', 'admin', $admin, 'text');
		showsetting('ec_orders_search_submit_date', array('sstarttime', 'sendtime'), array($sstarttime, $sendtime), 'daterange');
		showsetting('ec_orders_search_confirm_date', array('cstarttime', 'cendtime'), array($cstarttime, $cendtime), 'daterange');
		showsubmit('searchsubmit');
		showtablefooter();
		showformfooter();
		showtagfooter('div');
		/*search*/

		if(submitcheck('searchsubmit', 1)) {

			$start_limit = ($page - 1) * $_G['tpp'];


			$ordercount = C::t('forum_order')->count_by_search(null, $_GET['orderstatus'], $_GET['orderid'], null, ($_GET['users'] ? explode(',', str_replace(' ', '', $_GET['users'])) : null), $_GET['buyer'], $_GET['admin'], strtotime($_GET['sstarttime']), strtotime($_GET['sendtime']), strtotime($_GET['cstarttime']), strtotime($_GET['cendtime']));
			$multipage = multi($ordercount, $_G['tpp'], $page, ADMINSCRIPT."?action=ec&operation=orders&searchsubmit=yes&orderstatus={$_GET['orderstatus']}&orderid={$_GET['orderid']}&users={$_GET['users']}&buyer={$_GET['buyer']}&admin={$_GET['admin']}&sstarttime={$_GET['sstarttime']}&sendtime={$_GET['sendtime']}&cstarttime={$_GET['cstarttime']}&cendtime={$_GET['cendtime']}");

			showtagheader('div', 'orderlist', true);
			showformheader('ec&operation=orders');
			showtableheader('result');
			showsubtitle(array('', 'ec_orders_id', 'ec_orders_status', 'ec_orders_buyer', 'ec_orders_amount', 'ec_orders_price', 'ec_orders_submitdate', 'ec_orders_confirmdate'));


			foreach(C::t('forum_order')->fetch_all_by_search(null, $_GET['orderstatus'], $_GET['orderid'], null, ($_GET['users'] ? explode(',', str_replace(' ', '', $_GET['users'])) : null), $_GET['buyer'], $_GET['admin'], strtotime($_GET['sstarttime']), strtotime($_GET['sendtime']), strtotime($_GET['cstarttime']), strtotime($_GET['cendtime']), $start_limit, $_G['tpp']) as $order) {
				switch($order['status']) {
					case 1: $order['orderstatus'] = $lang['ec_orders_search_status_pending']; break;
					case 2: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_auto_finished'].'</b>'; break;
					case 3: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_manual_finished'].'</b><br />(<a href="home.php?mod=space&username='.rawurlencode($order['admin']).'" target="_blank">'.$order['admin'].'</a>)'; break;
				}
				$order['submitdate'] = dgmdate($order['submitdate']);
				$order['confirmdate'] = $order['confirmdate'] ? dgmdate($order['confirmdate']) : 'N/A';

				list($orderid, $apitype) = explode("\t", $order['buyer']);
				$apitype = $apitype ? $apitype : 'alipay';
				$orderid = '<a href="'.$orderurl[$apitype].$orderid.'" target="_blank">'.$orderid.'</a>';
				showtablerow('', '', array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"validate[]\" value=\"{$order['orderid']}\" ".($order['status'] != 1 ? 'disabled' : '').">",
					"{$order['orderid']}<br />$orderid",
					$order['orderstatus'],
					"<a href=\"home.php?mod=space&uid={$order['uid']}\" target=\"_blank\">{$order['username']}</a>",
					"{$_G['setting']['extcredits'][$_G['setting']['creditstrans']]['title']} {$order['amount']} {$_G['setting']['extcredits'][$_G['setting']['creditstrans']]['unit']}",
					"{$lang['rmb']} {$order['price']} {$lang['rmb_yuan']}",
					$order['submitdate'],
					$order['confirmdate']
				));
			}

			showsubmit('ordersubmit', 'submit', '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'validate\')" /><label for="chkall">'.cplang('ec_orders_validate').'</label>', '<a href="#" onclick="$(\'orderlist\').style.display=\'none\';$(\'ordersearch\').style.display=\'\';">'.cplang('research').'</a>', $multipage);
			showtablefooter();
			showformfooter();
			showtagfooter('div');
		}

	} else {

		$numvalidate = 0;
		if($_GET['validate']) {
			$orderids = array();
			$confirmdate = dgmdate(TIMESTAMP);

			foreach(C::t('forum_order')->fetch_all_order($_GET['validate'], '1') as $order) {
				updatemembercount($order['uid'], array($_G['setting']['creditstrans'] => $order['amount']));
				$orderids[] = $order['orderid'];

				$submitdate = dgmdate($order['submitdate']);
				notification_add($order['uid'], 'system', 'addfunds', array(
					'orderid' => $order['orderid'],
					'price' => $order['price'],
					'from_id' => 0,
					'from_idtype' => 'buycredit',
					'value' => $_G['setting']['extcredits'][$_G['setting']['creditstrans']]['title'].' '.$order['amount'].' '.$_G['setting']['extcredits'][$_G['setting']['creditstrans']]['unit']
				), 1);
			}
			if($orderids) {
				C::t('forum_order')->update($orderids, array('status' => '3', 'admin' => $_G['username'], 'confirmdate' => $_G['timestamp']));
			}
		}

		cpmsg('orders_validate_succeed', "action=ec&operation=orders&searchsubmit=yes&orderstatus={$_GET['orderstatus']}&orderid={$_GET['orderid']}&users={$_GET['users']}&buyer={$_GET['buyer']}&admin={$_GET['admin']}&sstarttime={$_GET['sstarttime']}&sendtime={$_GET['sendtime']}&cstarttime={$_GET['cstarttime']}&cendtime={$_GET['cendtime']}", 'succeed');

	}

} elseif($operation == 'credit') {

	$defaultrank = array(
		1 => 4,
		2 => 11,
		3 => 41,
		4 => 91,
		5 => 151,
		6 => 251,
		7 => 501,
		8 => 1001,
		9 => 2001,
		10 => 5001,
		11 => 10001,
		12 => 20001,
		13 => 50001,
		14 => 100001,
		15 => 200001
	);

	if(!submitcheck('creditsubmit')) {

		$ec_credit = C::t('common_setting')->fetch_setting('ec_credit', true);
		$ec_credit = $ec_credit ? $ec_credit : array(
			'maxcreditspermonth' => '6',
			'rank' => $defaultrank
		);

		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 0),
			array('nav_ec_wechat', 'ec&operation=wechat', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 1),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 0),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
		));

		/*search={"nav_ec":"action=setting&operation=ec","nav_ec_credit":"action=ec&operation=credit"}*/
		showtips('ec_credit_tips');
		showformheader('ec&operation=credit');
		showtableheader('ec_credit', 'nobottom');
		showsetting('ec_credit_maxcreditspermonth', 'ec_creditnew[maxcreditspermonth]', $ec_credit['maxcreditspermonth'], 'text');
		showtablefooter('</tbody>');
		/*search*/

		showtableheader('ec_credit_rank', 'notop fixpadding');
		showsubtitle(array('ec_credit_rank', 'ec_credit_between', 'ec_credit_sellericon', 'ec_credit_buyericon'));

		$staticurl = STATICURL;

		foreach($ec_credit['rank'] as $rank => $mincredits) {
			showtablerow('', '', array(
				$rank,
				'<input type="text" class="txt" size="6" name="ec_creditnew[rank]['.$rank.']" value="'.$mincredits.'" /> ~ '.$ec_credit['rank'][$rank + 1],
				"<img src=\"{$staticurl}image/traderank/seller/$rank.gif\" border=\"0\">",
				"<img src=\"{$staticurl}image/traderank/buyer/$rank.gif\" border=\"0\">"
			));
		}
		showsubmit('creditsubmit');
		showtablefooter();
		showformfooter();

	} else {
		$ec_creditnew = $_GET['ec_creditnew'];
		$ec_creditnew['maxcreditspermonth'] = intval($ec_creditnew['maxcreditspermonth']);

		if(is_array($ec_creditnew['rank'])) {
			foreach($ec_creditnew['rank'] as $rank => $mincredits) {
				$mincredits = intval($mincredits);
				if($rank == 1 && $mincredits <= 0) {
					cpmsg('ecommerce_invalidcredit', '', 'error');
				} elseif($rank > 1 && $mincredits <= $ec_creditnew['rank'][$rank - 1]) {
					cpmsg('ecommerce_must_larger', '', 'error', array('rank' => $rank));
				}
				$ec_creditnew['rank'][$rank] = $mincredits;
			}
		} else {
			$ec_creditnew['rank'] = $defaultrank;
		}

		C::t('common_setting')->update_setting('ec_credit', $ec_creditnew);
		updatecache('setting');

		cpmsg('ec_credit_succeed', 'action=ec&operation=credit', 'succeed');

	}
} elseif($operation == 'inviteorders') {
	if(!submitcheck('ordersubmit')) {
		$start_limit = ($page - 1) * $_G['tpp'];
		$orderurl = array(
			'alipay' => 'https://www.alipay.com/trade/query_trade_detail.htm?trade_no=',
			'tenpay' => 'https://www.tenpay.com/med/tradeDetail.shtml?trans_id=',
		);
		shownav('extended', 'nav_ec');
		showsubmenu('nav_ec', array(
			array('nav_ec_config', 'setting&operation=ec', 0),
			array('nav_ec_qpay', 'ec&operation=qpay', 0),
			array('nav_ec_wechat', 'ec&operation=wechat', 0),
			array('nav_ec_alipay', 'ec&operation=alipay', 0),
			array('nav_ec_credit', 'ec&operation=credit', 0),
			array('nav_ec_orders', 'ec&operation=orders', 0),
			array('nav_ec_tradelog', 'tradelog', 0),
			array('nav_ec_inviteorders', 'ec&operation=inviteorders', 1),
			array('nav_ec_paymentorders', 'ec&operation=paymentorders', 0),
			array('nav_ec_transferorders', 'ec&operation=transferorders', 0)
		));

		$ordercount = C::t('forum_order')->count_by_search(0, $_GET['orderstatus'], $_GET['orderid'], $_GET['email']);
		$multipage = multi($ordercount, $_G['tpp'], $page, ADMINSCRIPT."?action=ec&operation=inviteorders&orderstatus={$_GET['orderstatus']}&orderid={$_GET['orderid']}&email={$_GET['email']}");

		showtagheader('div', 'orderlist', TRUE);
		showformheader('ec&operation=inviteorders');
		showtableheader('ec_inviteorders_search');
		$_G['showsetting_multirow'] = 1;
		showsetting('ec_orders_search_status', array('orderstatus', array(
			array('', $lang['ec_orders_search_status_all']),
			array(1, $lang['ec_orders_search_status_pending']),
			array(2, $lang['ec_orders_search_status_auto_finished'])
		)), intval($_GET['orderstatus']), 'select');
		showsetting('ec_orders_search_id', 'orderid', $_GET['orderid'], 'text');
		showsetting('ec_orders_search_email', 'email', $_GET['email'], 'text');
		showsubmit('searchsubmit', 'submit');
		showtablefooter();
		showtableheader('result');
		showsubtitle(array('', 'ec_orders_id', 'ec_inviteorders_status', 'ec_inviteorders_buyer', 'ec_orders_amount', 'ec_orders_price', 'ec_orders_submitdate', 'ec_orders_confirmdate'));

		foreach(C::t('forum_order')->fetch_all_by_search(0, $_GET['orderstatus'], $_GET['orderid'], $_GET['email'], null, null, null, null, null, null, null, $start_limit, $_G['tpp']) as $order) {
			switch($order['status']) {
				case 1: $order['orderstatus'] = $lang['ec_orders_search_status_pending']; break;
				case 2: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_auto_finished'].'</b>'; break;
				case 3: $order['orderstatus'] = '<b>'.$lang['ec_orders_search_status_manual_finished'].'</b><br />(<a href="home.php?mod=space&username='.rawurlencode($order['admin']).'" target="_blank">'.$order['admin'].'</a>)'; break;
			}
			$order['submitdate'] = dgmdate($order['submitdate']);
			$order['confirmdate'] = $order['confirmdate'] ? dgmdate($order['confirmdate']) : 'N/A';

			list($orderid, $apitype) = explode("\t", $order['buyer']);
			$apitype = $apitype ? $apitype : 'alipay';
			$orderid = '<a href="'.$orderurl[$apitype].$orderid.'" target="_blank">'.$orderid.'</a>';
			showtablerow('', '', array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"validate[]\" value=\"{$order['orderid']}\" ".($order['status'] != 1 ? 'disabled' : '').">",
				"{$order['orderid']}<br />$orderid",
				$order['orderstatus'],
				"{$order['email']}<br>{$order['ip']}",
				$order['amount'],
				"{$lang['rmb']} {$order['price']} {$lang['rmb_yuan']}",
				$order['submitdate'],
				$order['confirmdate']
			));
		}
		showtablerow('', array('colspan="7"'), array($multipage));
		showsubmit('ordersubmit', 'ec_orders_validate', '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'validate\')" />');
		showtablefooter();
		showformfooter();
		showtagfooter('div');
	} else {
		if($_GET['validate']) {
			if(C::t('forum_order')->fetch_all_order($_GET['validate'], '1')) {
				C::t('forum_order')->update($_GET['validate'], array('status' => '3', 'admin' => $_G['username'], 'confirmdate' => $_G['timestamp']));
			}
		}
		cpmsg('orders_validate_succeed', "action=ec&operation=inviteorders&orderstatus={$_GET['orderstatus']}&orderid={$_GET['orderid']}&email={$_GET['email']}", 'succeed');
	}
}

?>