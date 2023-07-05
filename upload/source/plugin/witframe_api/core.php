<?php

namespace Lib;

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT . './source/plugin/witframe_api/lib/crypt.php';

use C;
use Crypt;
use Exception;

spl_autoload_register(function ($class) {
	if (substr($class, 0, 4) == 'Lib\\') {
		$f = strtolower(substr($class, 4));
		if (!preg_match('/^\w+$/', $f)) {
			return false;
		}
		require_once DISCUZ_ROOT . './source/plugin/witframe_api/lib/' . $f . '.php';
	}
}, true, true);

class Core {
	const WitApiURL = 'https://api.witframe.com/lib';

	const CoreClass = 'Lib\Core\Core';
	const SignExpire = 600;

	const SettingKey = 'witframe_v1';

	const Type_StaticMethod = 0;
	const Type_NewClass = 1;
	const Type_ObjMethod = 2;
	const Type_ApisMethod = 3;

	public static function RequestWit($class, $func, $param, $type = self::Type_StaticMethod) {
		if (!function_exists('curl_init') || !function_exists('curl_exec')) {
			throw new Exception('CURL is not enabled');
		}

		$baseConf = self::GetSetting();
		if ($baseConf) {
			if (empty($baseConf['witUid'])) {
				throw new Exception('witUid is not exists, check conf/config.ini');
			}
			if (empty($baseConf['witPid'])) {
				throw new Exception('witPid is not exists, check conf/config.ini');
			}
			if (empty($baseConf['witSecretId'])) {
				throw new Exception('witSecretId is not exists, check conf/config.ini');
			}
			if (empty($baseConf['witSecretKey'])) {
				throw new Exception('witSecretKey is not exists, check conf/config.ini');
			}
		} else {
			$secretId = substr(time(), 0, 7);
			$secretKey = md5($secretId);
			$baseConf = array(
				'witUid' => "0",
				'witSecretId' => $secretId,
				'witSecretKey' => $secretKey,
				'witPid' => "0",
				'ver' => 0,
			);
		}

		$requestBody = array(
			'witUid' => $baseConf['witUid'],
			'witPid' => $baseConf['witPid'],
			'class' => $class,
			'func' => $func,
			'param' => $param,
			'type' => $type,
		);
		$requestBody['t'] = time();
		$requestBody['sign'] = self::_getSign($baseConf['witSecretId'], $baseConf['witSecretKey'], $requestBody);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::WitApiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
		$response = curl_exec($ch);
		if (!$response) {
			throw new Exception($class . '::' . $func . '() response error');
		}
		$responseBody = json_decode($response, true);
		if (!$responseBody) {
			throw new Exception($class . '::' . $func . '() response error');
		}
		if ($func == 'Discuz_GetConf') {
			if ($responseBody['errCode']) {
				self::SetSetting(array());
				return array();
			}
			if (empty($baseConf['witUid']) || empty($baseConf['ver']) ||
				!empty($responseBody['data']['ver']) && $responseBody['data']['ver'] > $baseConf['ver']) {
				self::SetSetting($responseBody['data']);
			}
		}
		if ($responseBody['errCode']) {
			throw new Exception($class . '::' . $func . '() response ' . $responseBody['message'], $responseBody['errCode']);
		}
		return $responseBody['data'];
	}

	public static function GetSetting() {
		global $_G;
		if (!empty($_G['setting'][self::SettingKey])) {
			return unserialize($_G['setting'][self::SettingKey]);
		}
		return array();
	}

	public static function SetSetting($data) {
		C::t('common_setting')->update_batch(array(self::SettingKey => $data));
		require_once libfile('function/cache');
		updatecache('setting');
	}

	private static function _getSign($witSecretId, $witSecretKey, $data) {
		$srcStr = $witSecretId . '|' . serialize($data);
		return Crypt::encode($witSecretKey, 'sha1', $srcStr);
	}

}