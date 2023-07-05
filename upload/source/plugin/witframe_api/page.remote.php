<?php

chdir('../../../');

define('IN_WITFRAME_API_REMOTE', 1);
define('DISCUZ_OUTPUTED', 1);
define('IN_WITFRAME_API_REMOTE_DEBUG', !empty($_GET['_debug']) ? 1 : 0);


require_once './source/plugin/witframe_api/class/remote.class.php';

if (!empty($_POST)) {
	$r = new WitClass\Remote();
	if (empty($_POST['_script_'])) {
		$r->output(array(
			'ret' => -1,
		));
	}

	if (!preg_match('/^\w+$/', $_POST['_script_'])) {
		$r->output(array(
			'ret' => -2,
		));
	}

	$script = $_POST['_script_'];
	$session = !empty($_POST['_session_']) ? $_POST['_session_'] : '';
	if (!$r->check($script . $session)) {
		$r->output(array(
			'ret' => -4,
		));
	}

	$output = !empty($_POST['_output_']) ? $_POST['_output_'] : array();
	$rawOutput = !empty($_POST['_raw_']);
	$_GET = $r->paramDecode('_get_');
	$cookies = $session ? $r->sessionDecode($session) : array();
	foreach ($cookies as $k => $v) {
		$_COOKIE[$k] = $v;
		setcookie($k, $v);
	}
	$_POST = $r->paramDecode('_post_');

	$shutdownFunc = 'showOutput';
	if($rawOutput) {
		$shutdownFunc = 'rawOutput';
	} elseif($output) {
		$shutdownFunc = 'convertOutput';
	}

	register_shutdown_function(array($r, $shutdownFunc), $output);

	try {
		require './' . $script . '.php';
	} catch (Exception $e) {
		$r->output(array(
			'ret' => -3,
		));
	}
} else {
	$_GET['id'] = 'witframe_api:api';
	require './plugin.php';
}

