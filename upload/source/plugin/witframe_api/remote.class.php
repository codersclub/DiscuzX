<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class base_plugin_witframe_api {
	function global_witframe_api() {
		if (defined('IN_WITFRAME_API_REMOTE')) {
			exit;
		}
	}
}

class plugin_witframe_api extends base_plugin_witframe_api {
}

class mobileplugin_witframe_api extends plugin_witframe_api {
}