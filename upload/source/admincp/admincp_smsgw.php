<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_smsgw.php 34093 2013-10-09 05:41:18Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$root = '<a href="'.ADMINSCRIPT.'?action=smsgw">'.cplang('smsgw_admin').'</a>';

// list 表示短信网关列表, edit 表示编辑短信网关, setting 表示短信网关全局配置
$operation = $operation ? $operation : 'setting';

cpheader();

if($operation == 'list') {

	if(!submitcheck('smsgwsubmit')) {

		shownav('extended', 'smsgw_admin');
		showsubmenu($root.' &raquo; '.cplang('smsgw_admin_list'));

		showformheader("smsgw&operation=$operation");
		showtableheader('', 'fixpadding');
		showsubtitle(array('', 'order', 'available', 'name', 'type', ''));

		$flag = false;

		$classnames = array();
		$avaliablesmsgw = getsmsgws();
		foreach(C::t('common_smsgw')->fetch_all_gw_order_id() as $smsgw) {
			$smsgwfile = '';
			$etype = explode(':', $smsgw['class']);
			if(count($etype) > 1 && preg_match('/^[\w\_:]+$/', $smsgw['class'])) {
				$key = 'smsgw_'.$etype[1].'.php';
				if(array_key_exists($key, $avaliablesmsgw)) {
					$smsgwfile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/smsgw/smsgw_'.$etype[1].'.php';
					$smsgwclass = 'smsgw_'.$etype[1];
					unset($avaliablesmsgw[$key]);
				} else {
					C::t('common_smsgw')->update($smsgw['id'], array('available' => 0));
					$flag = true;
					continue;
				}
			} else {
				$key = 'smsgw_'.$smsgw['class'].'.php';
				if(array_key_exists($key, $avaliablesmsgw)) {
					$smsgwfile = libfile('smsgw/'.$smsgw['class'], 'class');
					$smsgwclass = 'smsgw_'.$smsgw['class'];
					unset($avaliablesmsgw[$key]);
				} else {
					C::t('common_smsgw')->update($smsgw['id'], array('available' => 0));
					$flag = true;
					continue;
				}
			}
			if(!isset($classnames[$smsgw['class']])) {
				require_once $smsgwfile;
				if(class_exists($smsgwclass)) {
					$smsgwclassv = new $smsgwclass();
					$classnames[$smsgw['class']] = lang('smsgw/'.$smsgw['class'], $smsgwclassv->name);
				} else {
					$classnames[$smsgw['class']] = $smsgw['class'];
				}
			}
			showtablerow('', array('class="td25"', 'class="td25"', 'class="td25"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"{$smsgw['smsgwid']}\" disabled=\"disabled\">",
				"<input type=\"text\" class=\"txt\" size=\"2\" name=\"ordernew[{$smsgw['smsgwid']}]\" value=\"{$smsgw['order']}\">",
				"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[{$smsgw['smsgwid']}]\" value=\"1\" ".($smsgw['available'] ? 'checked' : '').">",
				"<input type=\"text\" class=\"txt\" size=\"15\" name=\"namenew[{$smsgw['smsgwid']}]\" value=\"".dhtmlspecialchars($smsgw['name'])."\">",
				$smsgw['type'] == 0 ? cplang('smsgw_type_message') : cplang('smsgw_type_template'),
				"<a href=\"".ADMINSCRIPT."?action=smsgw&operation=edit&smsgwid={$smsgw['smsgwid']}\" class=\"act\">{$lang['edit']}</a>"
			));
		}
		// 如果有新增加的文件, 需要添加到列表内
		if(count($avaliablesmsgw) > 0) {
			foreach($avaliablesmsgw as $smsgw) {
				$arr = array('type' => $smsgw['type'], 'class' => $smsgw['class'], 'order' => 0, 'name' => $smsgw['name'], 'sendrule' => $smsgw['sendrule']);
				C::t('common_smsgw')->insert($arr);
				$flag = true;
			}
		}
		if($flag) {
			header("Location: ".ADMINSCRIPT."?action=smsgw&operation=$operation");
		}

		showsubmit('smsgwsubmit');

		showtablefooter();
		showformfooter();
	} else {

		if($_GET['delete']) {
			C::t('common_smsgw')->delete($_GET['delete']);
		}

		if(is_array($_GET['namenew'])) {
			foreach($_GET['namenew'] as $smsgwid => $title) {
				C::t('common_smsgw')->update($smsgwid, array(
					'available' => $_GET['availablenew'][$smsgwid],
					'order' => $_GET['ordernew'][$smsgwid],
					'name' => $_GET['namenew'][$smsgwid]
				));
			}
		}

		updatecache('setting');

		cpmsg('smsgw_succeed', dreferer(), 'succeed');

	}

} elseif($operation == 'edit' && !empty($_GET['smsgwid'])) {

	if(!submitcheck('smsgwsubmit')) {

		$smsgwid = $_GET['smsgwid'];
		$smsgw = C::t('common_smsgw')->fetch($smsgwid);
		if(!$smsgw) {
			cpmsg('smsgw_nonexistence', '', 'error');
		}
		$smsgw['parameters'] = dunserialize($smsgw['parameters']);
		$class = $smsgw['class'];

		$etype = explode(':', $class);
		if(count($etype) > 1 && preg_match('/^[\w\_:]+$/', $class)) {
			include_once DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/smsgw/smsgw_'.$etype[1].'.php';
			$smsgwclass = 'smsgw_'.$etype[1];
		} else {
			require_once libfile('smsgw/'.$class, 'class');
			$smsgwclass = 'smsgw_'.$class;
		}
		$smsgwclass = new $smsgwclass;
		$smsgwsetting = $smsgwclass->getsetting();
		$smsgwname = lang('smsgw/'.$class, $smsgwclass->name).' '.$smsgwclass->customname;
		$returnurl = 'action=smsgw&operation=list';

		$return = '<a href="'.ADMINSCRIPT.'?'.$returnurl.'">'.cplang('smsgw_admin_list').(empty($_GET['from']) ? ' - '.$smsgwname : '').'</a>';
		shownav('extended', 'smsgw_admin');
		showsubmenu($root.' &raquo; '.$return.' &raquo; ' . cplang('smsgw_edit'));

		showformheader("smsgw&operation=$operation&smsgwid=$smsgwid", 'enctype');
		showhiddenfields(array('referer' => $returnurl));
		showtableheader(cplang('smsgw_edit').' - '.lang('smsgw/'.$class, $smsgwclass->name), 'fixpadding');

		showsetting('smsgw_edit_name', 'smsgwnew[name]', $smsgw['name'], 'text');
		showsetting('smsgw_edit_order', 'smsgwnew[order]', $smsgw['order'], 'text');
		showsetting('smsgw_edit_sendrule', 'smsgwnew[sendrule]', $smsgw['sendrule'], 'text');
		if(is_array($smsgwsetting)) {
			foreach($smsgwsetting as $settingvar => $setting) {
				if(!empty($setting['value']) && is_array($setting['value'])) {
					foreach($setting['value'] as $k => $v) {
						$setting['value'][$k][1] = lang('smsgw/'.$class, $setting['value'][$k][1]);
					}
				}
				$varname = in_array($setting['type'], array('mradio', 'mcheckbox', 'select', 'mselect')) ?
					($setting['type'] == 'mselect' ? array('parameters['.$settingvar.'][]', $setting['value']) : array('parameters['.$settingvar.']', $setting['value']))
					: 'parameters['.$settingvar.']';
				$value = $smsgw['parameters'][$settingvar] != '' ? $smsgw['parameters'][$settingvar] : $setting['default'];
				$comment = lang('smsgw/'.$class, $setting['title'].'_comment');
				$comment = $comment != $setting['title'].'_comment' ? $comment : '';
				showsetting(lang('smsgw/'.$class, $setting['title']).':', $varname, $value, $setting['type'], '', 0, $comment);
			}
		}

		showsubmit('smsgwsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$smsgwid = $_GET['smsgwid'];
		$smsgw = C::t('common_smsgw')->fetch($smsgwid);
		$class = $smsgw['class'];
		$smsgw['parameters'] = dunserialize($smsgw['parameters']);

		$etype = explode(':', $class);
		if(count($etype) > 1 && preg_match('/^[\w\_:]+$/', $class)) {
			include_once DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/smsgw/smsgw_'.$etype[1].'.php';
			$smsgwclass = 'smsgw_'.$etype[1];
		} else {
			require_once libfile('smsgw/'.$class, 'class');
			$smsgwclass = 'smsgw_'.$class;
		}
		$smsgwclass = new $smsgwclass;

		$smsgwnew = $_GET['smsgwnew'];

		$parameters = !empty($_GET['parameters']) ? $_GET['parameters'] : array();
		$smsgwclass->setsetting($smsgwnew, $parameters);

		if(!$smsgwnew['name']) {
			cpmsg('smsgw_name_invalid', '', 'error');
		} elseif(strlen($smsgwnew['name']) > 255) {
			cpmsg('smsgw_name_more', '', 'error');
		}

		if(!$smsgwnew['sendrule']) {
			cpmsg('smsgw_sendrule_invalid', '', 'error');
		}

		C::t('common_smsgw')->update($smsgwid, array(
			'name' => $smsgwnew['name'],
			'order' => (int)$smsgwnew['order'],
			'sendrule' => $smsgwnew['sendrule'],
			'parameters' => serialize($parameters),
		));

		updatecache('setting');

		cpmsg('smsgw_succeed', 'action=smsgw&operation=edit&smsgwid='.$smsgwid, 'succeed');

	}

} elseif($operation == 'setting') {

	if(submitcheck('smsgwsubmit')) {
		// 是否开启 SMS
		$smsstatus = (int)$_GET['smsstatusnew'];
		// 默认国际电话区号, 默认 86
		$smsdefaultcc = (int)$_GET['smsdefaultccnew'];
		$smsdefaultcc = $smsdefaultcc > 0 ? $smsdefaultcc : 86;
		// 默认短信验证码长度, 默认 4
		$smsdefaultlength = (int)$_GET['smsdefaultlengthnew'];
		$smsdefaultlength = $smsdefaultlength > 0 ? $smsdefaultlength : 4;
		// 限制时间区间, 默认 86400 秒
		$smstimelimit = (int)$_GET['smstimelimitnew'];
		$smstimelimit = $smstimelimit > 0 ? $smstimelimit : 86400;
		// 单用户/单号码短信限制时间区间内总量, 默认 5 条
		$smsnumlimit = (int)$_GET['smsnumlimitnew'];
		$smsnumlimit = $smsnumlimit > 0 ? $smsnumlimit : 5;
		// 单用户/单号码短信时间间隔, 默认 300 秒
		$smsinterval = (int)$_GET['smsintervalnew'];
		$smsinterval = $smsinterval > 0 ? $smsinterval : 300;
		// 万号段短信限制时间区间内总量, 默认 20 条
		$smsmillimit = (int)$_GET['smsmillimitnew'];
		$smsmillimit = $smsmillimit > 0 ? $smsmillimit : 20;
		// 全局短信限制时间区间内总量, 默认 1000 条
		$smsglblimit = (int)$_GET['smsglblimitnew'];
		$smsglblimit = $smsglblimit > 0 ? $smsglblimit : 1000;

		C::t('common_setting')->update_setting('smsstatus', $smsstatus);
		C::t('common_setting')->update_setting('smsdefaultcc', $smsdefaultcc);
		C::t('common_setting')->update_setting('smsdefaultlength', $smsdefaultlength);
		C::t('common_setting')->update_setting('smstimelimit', $smstimelimit);
		C::t('common_setting')->update_setting('smsnumlimit', $smsnumlimit);
		C::t('common_setting')->update_setting('smsinterval', $smsinterval);
		C::t('common_setting')->update_setting('smsmillimit', $smsmillimit);
		C::t('common_setting')->update_setting('smsglblimit', $smsglblimit);

		updatecache('setting');

		cpmsg('setting_update_succeed', 'action=smsgw&operation=setting', 'succeed');
	} else {
		shownav('extended', 'smsgw_admin');
		showsubmenu('smsgw_admin', array(
			array('smsgw_admin_setting', 'smsgw&operation=setting', 1),
			array('smsgw_admin_list', 'smsgw&operation=list', 0)
		));
		// 是否开启 SMS
		$smsstatus = C::t('common_setting')->fetch_setting('smsstatus');
		// 默认国际区号, 默认 86
		$smsdefaultcc = C::t('common_setting')->fetch_setting('smsdefaultcc');
		// 默认短信验证码长度, 默认 4
		$smsdefaultlength = C::t('common_setting')->fetch_setting('smsdefaultlength');
		// 限制时间区间, 默认 86400 秒
		$smstimelimit = C::t('common_setting')->fetch_setting('smstimelimit');
		// 单用户/单号码短信限制时间区间内总量, 默认 5 条
		$smsnumlimit = C::t('common_setting')->fetch_setting('smsnumlimit');
		// 单用户/单号码短信时间间隔, 默认 300 秒
		$smsinterval = C::t('common_setting')->fetch_setting('smsinterval');
		// 万号段短信限制时间区间内总量, 默认 20 条
		$smsmillimit = C::t('common_setting')->fetch_setting('smsmillimit');
		// 全局短信限制时间区间内总量, 默认 1000 条
		$smsglblimit = C::t('common_setting')->fetch_setting('smsglblimit');

		showformheader("smsgw&operation=$operation");
		showtableheader();
		showsetting('smsgw_setting_smsstatus', 'smsstatusnew', $smsstatus, 'radio', 0, 1);
		showsetting('smsgw_setting_smsdefaultcc', 'smsdefaultccnew', $smsdefaultcc, 'text');
		showsetting('smsgw_setting_smsdefaultlength', 'smsdefaultlengthnew', $smsdefaultlength, 'text');
		showsetting('smsgw_setting_smstimelimit', 'smstimelimitnew', $smstimelimit, 'text');
		showsetting('smsgw_setting_smsnumlimit', 'smsnumlimitnew', $smsnumlimit, 'text');
		showsetting('smsgw_setting_smsinterval', 'smsintervalnew', $smsinterval, 'text');
		showsetting('smsgw_setting_smsmillimit', 'smsmillimitnew', $smsmillimit, 'text');
		showsetting('smsgw_setting_smsglblimit', 'smsglblimitnew', $smsglblimit, 'text');
		showtagfooter('tbody');
		showsubmit('smsgwsubmit');
		showtablefooter();
		showformfooter();
	}

}

function getsmsgws() {
	global $_G;
	$checkdirs = array_merge(array(''), $_G['setting']['plugins']['available']);
	$smsgws = array();
	foreach($checkdirs as $key) {
		if($key) {
			$dir = DISCUZ_ROOT.'./source/plugin/'.$key.'/smsgw';
		} else {
			$dir = DISCUZ_ROOT.'./source/class/smsgw';
		}
		if(!file_exists($dir)) {
			continue;
		}
		$smsgwdir = dir($dir);
		while($entry = $smsgwdir->read()) {
			if(!in_array($entry, array('.', '..')) && preg_match("/^smsgw\_[\w\.]+$/", $entry) && substr($entry, -4) == '.php' && strlen($entry) < 30 && is_file($dir.'/'.$entry)) {
				@include_once $dir.'/'.$entry;
				$smsgwclass = substr($entry, 0, -4);
				if(class_exists($smsgwclass)) {
					$smsgw = new $smsgwclass();
					$script = substr($smsgwclass, 6);
					$script = ($key ? $key.':' : '').$script;
					$smsgws[$entry] = array(
						'class' => $script,
						'name' => lang('smsgw/'.$script, $smsgw->name),
						'version' => $smsgw->version,
						'copyright' => lang('smsgw/'.$script, $smsgw->copyright),
						'type' => $smsgw->type,
						'sendrule' => $smsgw->sendrule,
						'filemtime' => @filemtime($dir.'/'.$entry)
					);
				}
			}
		}
	}
	uasort($smsgws, 'filemtimesort');
	return $smsgws;
}

?>