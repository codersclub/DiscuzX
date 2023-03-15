<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: magic_chgusername.php 2248 2020-02-16 00:00:00Z community $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class magic_chgusername {

	var $version = '1.0';
	var $name = 'chgusername_name';
	var $description = 'chgusername_desc';
	var $price = '10';
	var $weight = '10';
	var $useevent = 1;
	var $targetgroupperm = true;
	var $copyright = '<a href="https://www.discuz.vip" target="_blank">Discuz! Community Team</a>';
	var $magic = array();
	var $parameters = array();

	function getsetting(&$magic) {
	}

	function setsetting(&$magicnew, &$parameters) {
	}

	function usesubmit() {
		global $_G;
		if(empty($_GET['newusername'])) {
			showmessage(lang('magic/chgusername', 'chgusername_info_nonexistence'));
		}

		$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($_G['settting']['censoruser'] = trim($_G['settting']['censoruser'])), '/')).')$/i';

		if($_G['settting']['censoruser'] && @preg_match($censorexp, $_GET['newusername'])) {
			showmessage(lang('magic/chgusername', 'chgusername_name_badword'));
		}

		loaducenter();
		$ucresult = uc_user_chgusername($_G['uid'], addslashes(trim($_GET['newusername'])));

		if($ucresult < 0) {
			if($ucresult == -1) {
				showmessage(lang('magic/chgusername', 'chgusername_check_failed'));
			} elseif($ucresult == -2) {
				showmessage(lang('magic/chgusername', 'chgusername_name_badword'));
			} elseif($ucresult == -3) {
				showmessage(lang('magic/chgusername', 'chgusername_name_exists'));
			} else {
				showmessage(lang('magic/chgusername', 'chgusername_change_failed'));
			}
		}

		usemagic($this->magic['magicid'], $this->magic['num']);
		updatemagiclog($this->magic['magicid'], '2', '1', '0', 0, 'uid', $_G['uid']);

		showmessage(lang('magic/chgusername', 'chgusername_change_success'), '', '', array('alert' => 'info', 'showdialog' => 1));
	}

	function show() {
		magicshowtype('top');
		magicshowsetting(lang('magic/chgusername', 'chgusername_newusername'), 'newusername', '', 'text');
		magicshowtype('bottom');
	}

}

?>