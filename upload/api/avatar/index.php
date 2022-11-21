<?php

require_once '../../source/class/class_core.php';

$discuz = C::app();
$discuz->init();
if(getgpc('m') !== 'user' || getgpc('a') !== 'rectavatar') {
	exit;
}
loaducenter();
if(!UC_AVTPATH) {
	$avtpath = './data/avatar/';
} else {
	$avtpath = str_replace('..', '', UC_AVTPATH);
}
define('UC_UPAVTDIR', realpath(DISCUZ_ROOT.$avtpath).'/');
if(!empty($_G['uid'])) {
	echo uc_rectavatar($_G['uid']);
} else {
	echo uc_rectavatar(0);
}