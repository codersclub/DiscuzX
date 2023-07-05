<?php

namespace Lib;

use Exception;

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Apis {

	public static function __callStatic($name, $arguments) {
		list($plugin, $identifier, $interface, $action) = explode('_', $name);
		if (!preg_match('/^[A-Z]\w+$/', $plugin) ||
			!preg_match('/^\w+$/', $identifier) ||
			!preg_match('/^\w+$/', $interface)) {
			throw new Exception('plugin identifier is invalid', Wit_Const::Error_API_Identifier_Invalid);
		}

		if (!$action) {
			$action = 'index';
		} elseif (!preg_match('/^\w+$/', $action)) {
			throw new Exception('plugin identifier is invalid', Wit_Const::Error_API_Identifier_Invalid);
		}

		return Core::RequestWit(__CLASS__, $name, $arguments, Core::Type_ApisMethod);
	}

}