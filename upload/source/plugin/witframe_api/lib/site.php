<?php

namespace Lib;

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Site {

	public static function __callStatic($name, $arguments) {
		return Core::RequestWit(__CLASS__, $name, $arguments, Core::Type_StaticMethod);
	}

}