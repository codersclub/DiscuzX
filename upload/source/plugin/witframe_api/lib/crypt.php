<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Crypt {

	protected static $_key = null;

	protected static $_hashAlgorithm = 'md5';

	protected static $_supportedMhashAlgorithms = array('adler32', ' crc32', 'crc32b', 'gost',
		'haval128', 'haval160', 'haval192', 'haval256', 'md4', 'md5', 'ripemd160',
		'sha1', 'sha256', 'tiger', 'tiger128', 'tiger160');

	const STRING = 'string';
	const BINARY = 'binary';

	protected static $_supportedAlgosMhash = array(
		'adler32',
		'crc32',
		'crc32b',
		'gost',
		'haval128',
		'haval160',
		'haval192',
		'haval256',
		'md4',
		'md5',
		'ripemd160',
		'sha1',
		'sha256',
		'tiger',
		'tiger128',
		'tiger160'
	);

	public static function encode($key, $hash, $data, $output = self::STRING) {

		// set the key
		if (!isset($key) || empty($key)) {
			throw new Exception('provided key is null or empty');
		}
		self::$_key = $key;

		// set the hash
		self::_setHashAlgorithm($hash);

		// perform hashing and return
		return self::_hash($data, $output);
	}

	protected static function _setHashAlgorithm($hash) {

		if (!isset($hash) || empty($hash)) {
			throw new Exception('provided hash string is null or empty');
		}

		$hash = strtolower($hash);
		$hashSupported = false;

		if (function_exists('hash_algos') && in_array($hash, hash_algos())) {
			$hashSupported = true;
		}

		if ($hashSupported === false && function_exists('mhash') && in_array($hash, self::$_supportedAlgosMhash)) {
			$hashSupported = true;
		}

		if ($hashSupported === false) {
			throw new Exception('hash algorithm provided is not supported on this PHP installation; please enable the hash or mhash extensions');
		}

		self::$_hashAlgorithm = $hash;
	}

	protected static function _hash($data, $output = self::STRING) {

		if (function_exists('hash_hmac')) {
			if ($output == self::BINARY) {
				return hash_hmac(self::$_hashAlgorithm, $data, self::$_key, 1);
			}
			return hash_hmac(self::$_hashAlgorithm, $data, self::$_key);
		}

		if (function_exists('mhash')) {
			if ($output == self::BINARY) {
				return mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key);
			}
			$bin = mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key);
			return bin2hex($bin);
		}
	}

	protected static function _getMhashDefinition($hashAlgorithm) {

		for ($i = 0; $i <= mhash_count(); $i++) {
			$types[mhash_get_hash_name($i)] = $i;
		}
		return $types[strtoupper($hashAlgorithm)];
	}

}
