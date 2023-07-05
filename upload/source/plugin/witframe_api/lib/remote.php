<?php

namespace Lib;

use C;

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT . './source/plugin/witframe_api/class/remote.class.php';
loaducenter();

class Remote {

	const Ret_Success = 0;
	const Ret_AuthFail = -1;
	const Ret_ParamFail = -2;

	const AuthExpire = 300;

	var $method = '';
	var $get = array();
	var $post = array();
	var $r;

	private static $_instance;

	public static function getInstance() {
		self::$_instance = new self();
		return self::$_instance;
	}

	public function run() {
		$get = $post = array();

		if (empty($_GET['code'])) {
			$this->_return(self::Ret_AuthFail);
		}
		parse_str(authcode($_GET['code'], 'DECODE', UC_KEY), $get);

		if (time() - $get['t'] > self::AuthExpire) {
			$this->_return(self::Ret_AuthFail);
		}

		if (empty($get['action'])) {
			$this->_return(self::Ret_ParamFail);
		}

		$this->method = '_action_' . $get['action'];
		if (!method_exists($this, $this->method)) {
			$this->_return(self::Ret_ParamFail);
		}

		$this->get = $get;
		$this->post = !empty($_POST) ? $_POST : array();

		$this->r = new \WitClass\Remote(UC_CHARSET);

		return call_user_func(array($this, $this->method));
	}

	private function _action_test() {
		$this->_return(self::Ret_Success, array('time' => time()));
	}

	private function _action_getUser() {
		$user = array();
		if (!empty($this->get['username'])) {
			$this->get['username'] = $this->r->iconv($this->get['username'], 'UTF-8', UC_CHARSET);
			$user = uc_get_user($this->get['username']);
		} elseif (!empty($this->get['uid'])) {
			$user = uc_get_user($this->get['uid'], 1);
		}
		if (!$user) {
			$this->_return(self::Ret_Success);
		}
		$return = array('errCode' => 0);
		list($return['uid'], $return['username'], $return['email']) = $user;
		$return['avatar'] = $this->_getAvatar($return['uid']);
		$return['count'] = C::t('common_member_count')->fetch($return['uid']);
		$this->_return(self::Ret_Success, $return);
	}

	private function _action_getSiteInfo() {
		global $_G;
		$return['siteName'] = $_G['setting']['bbname'];
		$return['extcredits'] = $_G['setting']['extcredits'];
		$this->_return(self::Ret_Success, $return);
	}

	private function _action_login() {
		if (empty($this->get['password'])) {
			$this->_return(self::Ret_ParamFail);
		}
		$name = '';
		$isUid = 0;
		if (!empty($this->get['username'])) {
			$name = $this->get['username'];
		} elseif (!empty($this->get['uid'])) {
			$name = $this->get['uid'];
			$isUid = 1;
		} elseif (!empty($this->get['email'])) {
			$name = $this->get['email'];
			$isUid = 2;
		} else {
			$this->_return(self::Ret_ParamFail);
		}
		$user = uc_user_login($name, $this->get['password'], $isUid);
		if (!$user) {
			$this->_return(self::Ret_Success);
		}
		list($status) = $user;
		if ($status <= 0) {
			$this->_return(self::Ret_Success, array('errCode' => $status));
		}
		$return = array('errCode' => 0);
		list($return['uid'], $return['username'], , $return['email']) = $user;
		$return['avatar'] = $this->_getAvatar($return['uid']);
		$this->_return(self::Ret_Success, $return);
	}

	private function _getAvatar($uid) {
		return UC_API . '/avatar.php?uid=' . $uid . '&size=middle';
	}

	private function _return($ret, $data = array()) {
		$this->r->output(array(
			'ret' => $ret,
			'data' => $data,
		));
	}
}