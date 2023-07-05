<?php

namespace WitClass;

class Remote {

	private $charset = '';

	public function __construct($charset = '') {
		if ($charset) {
			$this->charset = strtolower($charset);
		}
	}

	public function paramDecode($key) {
		if (empty($_POST[$key])) {
			return array();
		}

		return $this->iconv(!is_array($_POST[$key]) ? unserialize($_POST[$key]) : $_POST[$key], 'UTF-8', $this->charset);
	}

	public function check($hash) {
		require 'config/config_ucenter.php';

		$this->charset = strtolower(UC_CHARSET);

		$t = substr(time(), 0, 7);
		$code = sha1($hash . UC_KEY . $t);
		return $code == $_GET['code'];
	}

	public function output($value) {
		echo json_encode($this->iconv($value, $this->charset, 'UTF-8'));
		exit;
	}

	public function showOutput() {
		$return = array('ret' => 0);

		$s = ob_get_contents();
		ob_end_clean();
		$return['data']['content'] = $s;

		$this->_setSysVar($return['data']);
		$this->output($return);
	}

	public function rawOutput() {
		exit;
	}

	public function convertOutput($output) {
		ob_end_clean();
		$return = array('ret' => 0);

		$this->_setSysVar($return['data'], $output);
		$tmp = $GLOBALS;
		foreach ($output as $k => $v) {
			if (strpos($k, '/') !== false) {
				$return['data'][$v] = $this->_arrayVar($tmp, $k);
			} else {
				$return['data'][$v] = $this->_singleVar($tmp, $k);
			}
		}

		$this->output($return);
	}

	public function sessionDecode($v) {
		return unserialize(base64_decode($v));
	}

	private function _sessionEncode($v) {
		return base64_encode(serialize($v));
	}

	private function _setSysVar(&$data, &$output = array()) {
		global $_G;
		$data['_session'] = $this->_sessionEncode($_COOKIE);
		$data['_formhash'] = $this->_singleVar($_G, 'formhash');
		if (isset($output['_attachhash'])) {
			if (!empty($_G['config']['security']['authkey'])) {
				$data['_attachhash'] = md5(substr(md5($_G['config']['security']['authkey']), 8) . $_G['uid']);
			}
			unset($output['_attachhash']);
		}

		unset($_G['config'],
			$_G['setting']['siteuniqueid'],
			$_G['setting']['ec_tenpay_opentrans_chnid'],
			$_G['setting']['ec_tenpay_opentrans_key'],
			$_G['setting']['ec_tenpay_bargainor'],
			$_G['setting']['ec_tenpay_key'],
			$_G['setting']['ec_account'],
			$_G['setting']['ec_contract']);
	}

	private function _singleVar(&$var, $k) {
		return isset($var[$k]) ? $var[$k] : null;
	}

	private function _arrayVar(&$var, $k) {
		$value = null;
		$sVar = &$var;
		$e = explode('/', $k);
		$count = count($e);
		foreach ($e as $i => $_k) {
			if ($_k == '*') {
				foreach ($sVar as $_k3 => $_v3) {
					$nKey = implode('/', array_slice($e, $i + 1));
					$value[$_k3] = $this->_arrayVar($_v3, $nKey);
				}
				break;
			}
			$isMulti = strpos($_k, ',') !== false;
			if (!isset($sVar[$_k]) && !$isMulti) {
				break;
			}
			if ($isMulti) {
				$value = null;
				foreach (explode(',', $_k) as $_k2) {
					$value[$_k2] = $this->_singleVar($sVar, $_k2);
				}
				break;
			} else {
				if ($count - 1 == $i) {
					$value = $this->_singleVar($sVar, $_k);
				}
				$sVar = &$sVar[$_k];
			}
		}
		return $value;
	}

	public function iconv($variables, $in_charset, $out_charset) {
		if ($this->charset == 'utf-8') {
			return $variables;
		}
		if (is_string($variables)) {
			return $this->_iconvStr($variables, $in_charset, $out_charset);
		}
		if (is_array($variables)) {
			foreach ($variables as $k => $v) {
				if (is_array($v)) {
					$variables[$k] = $this->iconv($v, $in_charset, $out_charset);
				} elseif (is_string($v)) {
					$variables[$k] = $this->_iconvStr($v, $in_charset, $out_charset);
				}
			}
		}

		return $variables;
	}

	private function _iconvStr($v, $in_charset, $out_charset) {
		if (function_exists('diconv')) {
			return diconv($v, $in_charset, $out_charset);
		} elseif (function_exists('iconv')) {
			return iconv($in_charset, $out_charset, $v);
		} else {
			return $v;
		}
	}

}