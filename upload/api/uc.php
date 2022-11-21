<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: uc.php 36358 2017-01-20 02:05:50Z nemohou $
 */

error_reporting(0);

define('API_DELETEUSER', 1);
define('API_RENAMEUSER', 1);
define('API_GETTAG', 1);
define('API_SYNLOGIN', 1);
define('API_SYNLOGOUT', 1);
define('API_UPDATEPW', 1);
define('API_UPDATEBADWORDS', 1);
define('API_UPDATEHOSTS', 1);
define('API_UPDATEAPPS', 1);
define('API_UPDATECLIENT', 1);
define('API_UPDATECREDIT', 1);
define('API_GETCREDIT', 1);
define('API_GETCREDITSETTINGS', 1);
define('API_UPDATECREDITSETTINGS', 1);
define('API_ADDFEED', 1);
define('API_RETURN_SUCCEED', '1');
define('API_RETURN_FAILED', '-1');
define('API_RETURN_FORBIDDEN', '1');

define('IN_API', true);
define('CURSCRIPT', 'api');
define('DISABLEXSSCHECK', true);

if(!defined('IN_UC')) {
	require_once '../source/class/class_core.php';

	$discuz = C::app();
	$discuz->init();

	require DISCUZ_ROOT.'./config/config_ucenter.php';
	require DISCUZ_ROOT.'./uc_client/release/release.php';

	$get = $post = array();

	$code = @$_GET['code'];
	parse_str(authcode($code, 'DECODE', UC_KEY), $get);

	if(empty($get) || UC_STANDALONE) {
		exit('Invalid Request');
	}
	if(time() - $get['time'] > 3600) {
		exit('Authorization has expired');
	}

	include_once DISCUZ_ROOT.'./uc_client/lib/xml.class.php';
	$phpinput = file_get_contents('php://input');
	$post = xml_unserialize($phpinput);

	// 考虑到第三方应用接入独立模式的成本问题, 此处将 uc.php 里的业务相关代码剥离出去, 放进专门的 extend_client.php 里解决
	require DISCUZ_ROOT.'./uc_client/extend_client.php';

	if(in_array($get['action'], array('test', 'deleteuser', 'renameuser', 'gettag', 'synlogin', 'synlogout', 'updatepw', 'updatebadwords', 'updatehosts', 'updateapps', 'updateclient', 'updatecredit', 'getcredit', 'getcreditsettings', 'updatecreditsettings', 'addfeed'))) {
		$uc_note = new uc_note();
		echo call_user_func(array($uc_note, $get['action']), $get, $post);
		exit();
	} else {
		exit(API_RETURN_FAILED);
	}
} else {
	exit;
}

class uc_note {

	var $dbconfig = '';
	var $db = '';
	var $tablepre = '';
	var $appdir = '';

	function _serialize($arr, $htmlon = 0) {
		if(!function_exists('xml_serialize')) {
			include_once DISCUZ_ROOT.'./uc_client/lib/xml.class.php';
		}
		return xml_serialize($arr, $htmlon);
	}

	function __construct() {
	}

	function test($get, $post) {
		return API_RETURN_SUCCEED;
	}

	function deleteuser($get, $post) {
		global $_G;
		if(!API_DELETEUSER) {
			return API_RETURN_FORBIDDEN;
		}

		return uc_note_handler::deleteuser($get, $post);
	}

	function renameuser($get, $post) {
		global $_G;

		if(!API_RENAMEUSER) {
			return API_RETURN_FORBIDDEN;
		}
		return uc_note_handler::renameuser($get, $post);
	}

	function gettag($get, $post) {
		global $_G;
		if(!API_GETTAG) {
			return API_RETURN_FORBIDDEN;
		}
		return $this->_serialize(array($get['id'], array()), 1);
	}

	function synlogin($get, $post) {
		global $_G;

		if(!API_SYNLOGIN) {
			return API_RETURN_FORBIDDEN;
		}

		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

		$cookietime = 31536000;
		$uid = intval($get['uid']);
		if(($member = getuserbyuid($uid, 1))) {
			dsetcookie('auth', authcode("{$member['password']}\t{$member['uid']}", 'ENCODE'), $cookietime);
		}
	}

	function synlogout($get, $post) {
		global $_G;

		if(!API_SYNLOGOUT) {
			return API_RETURN_FORBIDDEN;
		}

		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

		dsetcookie('auth', '', -31536000);
	}

	function updatepw($get, $post) {
		global $_G;

		if(!API_UPDATEPW) {
			return API_RETURN_FORBIDDEN;
		}
		return uc_note_handler::updatepw($get, $post);
	}

	function updatebadwords($get, $post) {
		global $_G;

		if(!API_UPDATEBADWORDS) {
			return API_RETURN_FORBIDDEN;
		}

		$data = array();
		if(is_array($post)) {
			foreach($post as $k => $v) {
				if(substr($v['findpattern'], 0, 1) != '/' || substr($v['findpattern'], -3) != '/is') {
					$v['findpattern'] = '/' . preg_quote($v['findpattern'], '/') . '/is';
				}
				$data['findpattern'][$k] = $v['findpattern'];
				$data['replace'][$k] = $v['replacement'];
			}
		}
		$cachefile = DISCUZ_ROOT.'./uc_client/data/cache/badwords.php';
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'badwords\'] = '.var_export($data, TRUE).";\r\n";

		if(file_put_contents($cachefile, $s, LOCK_EX) === false) {
			return API_RETURN_FAILED;
		}

		return API_RETURN_SUCCEED;
	}

	function updatehosts($get, $post) {
		global $_G;

		if(!API_UPDATEHOSTS) {
			return API_RETURN_FORBIDDEN;
		}

		$cachefile = DISCUZ_ROOT.'./uc_client/data/cache/hosts.php';
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'hosts\'] = '.var_export($post, TRUE).";\r\n";

		if(file_put_contents($cachefile, $s, LOCK_EX) === false) {
			return API_RETURN_FAILED;
		}

		return API_RETURN_SUCCEED;
	}

	function updateapps($get, $post) {
		global $_G;

		if(!API_UPDATEAPPS) {
			return API_RETURN_FORBIDDEN;
		}

		$UC_API = '';
		if($post['UC_API']) {
			$UC_API = str_replace(array('\'', '"', '\\', "\0", "\n", "\r"), '', $post['UC_API']);
			unset($post['UC_API']);
		}

		$cachefile = DISCUZ_ROOT.'./uc_client/data/cache/apps.php';
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'apps\'] = '.var_export($post, TRUE).";\r\n";

		if(file_put_contents($cachefile, $s, LOCK_EX) === false) {
			return API_RETURN_FAILED;
		}

		if($UC_API && is_writeable(DISCUZ_ROOT.'./config/config_ucenter.php')) {
			if(preg_match('/^https?:\/\//is', $UC_API)) {
				require DISCUZ_ROOT.'./config/config_ucenter.php';
				$configfile = trim(file_get_contents(DISCUZ_ROOT.'./config/config_ucenter.php'));
				$configfile = substr($configfile, -2) == '?>' ? substr($configfile, 0, -2) : $configfile;
				$configfile = str_replace("define('UC_API', '".addslashes(UC_API)."')", "define('UC_API', '".addslashes($UC_API)."')", $configfile);

				if(file_put_contents(DISCUZ_ROOT.'./config/config_ucenter.php', trim($configfile)) === false) {
					return API_RETURN_FAILED;
				}
			}
		}
		return API_RETURN_SUCCEED;
	}

	function updateclient($get, $post) {
		global $_G;

		if(!API_UPDATECLIENT) {
			return API_RETURN_FORBIDDEN;
		}

		$cachefile = DISCUZ_ROOT.'./uc_client/data/cache/settings.php';
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'settings\'] = '.var_export($post, TRUE).";\r\n";

		if(file_put_contents($cachefile, $s, LOCK_EX) === false) {
			return API_RETURN_FAILED;
		}

		return API_RETURN_SUCCEED;
	}

	function updatecredit($get, $post) {
		global $_G;

		if(!API_UPDATECREDIT) {
			return API_RETURN_FORBIDDEN;
		}

		$credit = $get['credit'];
		$amount = $get['amount'];
		$uid = $get['uid'];
		if(!getuserbyuid($uid)) {
			return API_RETURN_SUCCEED;
		}

		updatemembercount($uid, array($credit => $amount));
		C::t('common_credit_log')->insert(array('uid' => $uid, 'operation' => 'ECU', 'relatedid' => $uid, 'dateline' => time(), 'extcredits'.$credit => $amount));

		return API_RETURN_SUCCEED;
	}

	function getcredit($get, $post) {
		global $_G;

		if(!API_GETCREDIT) {
			return API_RETURN_FORBIDDEN;
		}
		$uid = intval($get['uid']);
		$credit = intval($get['credit']);
		$_G['uid'] = $_G['member']['uid'] = $uid;
		return getuserprofile('extcredits'.$credit);
	}

	function getcreditsettings($get, $post) {
		global $_G;

		if(!API_GETCREDITSETTINGS) {
			return API_RETURN_FORBIDDEN;
		}

		$credits = array();
		foreach($_G['setting']['extcredits'] as $id => $extcredits) {
			$credits[$id] = array(strip_tags($extcredits['title']), $extcredits['unit']);
		}

		return $this->_serialize($credits);
	}

	function updatecreditsettings($get, $post) {
		global $_G;

		if(!API_UPDATECREDITSETTINGS) {
			return API_RETURN_FORBIDDEN;
		}

		$outextcredits = array();
		foreach($get['credit'] as $appid => $credititems) {
			if($appid == UC_APPID) {
				foreach($credititems as $value) {
					$outextcredits[$value['appiddesc'].'|'.$value['creditdesc']] = array(
						'appiddesc' => $value['appiddesc'],
						'creditdesc' => $value['creditdesc'],
						'creditsrc' => $value['creditsrc'],
						'title' => $value['title'],
						'unit' => $value['unit'],
						'ratiosrc' => $value['ratiosrc'],
						'ratiodesc' => $value['ratiodesc'],
						'ratio' => $value['ratio']
					);
				}
			}
		}
		$tmp = array();
		foreach($outextcredits as $value) {
			$key = $value['appiddesc'].'|'.$value['creditdesc'];
			if(!isset($tmp[$key])) {
				$tmp[$key] = array('title' => $value['title'], 'unit' => $value['unit']);
			}
			$tmp[$key]['ratiosrc'][$value['creditsrc']] = $value['ratiosrc'];
			$tmp[$key]['ratiodesc'][$value['creditsrc']] = $value['ratiodesc'];
			$tmp[$key]['creditsrc'][$value['creditsrc']] = $value['ratio'];
		}
		$outextcredits = $tmp;

		$cachefile = DISCUZ_ROOT.'./uc_client/data/cache/creditsettings.php';
		$s = "<?php\r\n";
		$s .= '$_CACHE[\'creditsettings\'] = '.var_export($outextcredits, TRUE).";\r\n";

		if(file_put_contents($cachefile, $s, LOCK_EX) === false) {
			return API_RETURN_FAILED;
		}

		return API_RETURN_SUCCEED;
	}

	function addfeed($get, $post) {
		global $_G;

		if(!API_ADDFEED) {
			return API_RETURN_FORBIDDEN;
		}
		return API_RETURN_SUCCEED;
	}
}