<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT . './source/plugin/witframe_api/core.php';

Lib\Remote::getInstance()->run();


