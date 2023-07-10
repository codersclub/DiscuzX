<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: install_function.php 36362 2017-02-04 02:02:03Z nemohou $
 */

if(!defined('IN_COMSENZ')) {
	exit('Access Denied');
}

function show_msg($error_no, $error_msg = 'ok', $success = 1, $quit = TRUE) {
	if(VIEW_OFF) {
		$error_code = $success ? 0 : constant(strtoupper($error_no));
		$error_msg = empty($error_msg) ? $error_no : $error_msg;
		$error_msg = str_replace('"', '\"', $error_msg);
		$str = "<root>\n";
		$str .= "\t<error errorCode=\"$error_code\" errorMessage=\"$error_msg\" />\n";
		$str .= "</root>";
		send_mime_type_header();
		echo $str;
		exit;
	} else {
		show_header();
		global $step;

		$title = lang($error_no);
		$comment = lang($error_no.'_comment', false);
		$errormsg = '';

		if($error_msg) {
			if(!empty($error_msg)) {
				foreach ((array)$error_msg as $k => $v) {
					if(is_numeric($k)) {
						$comment .= "<li><em class=\"red\">".lang($v)."</em></li>";
					}
				}
			}
		}

		if($step > 0) {
			echo "<div class=\"box warnbox\"><h3>$title</h3><ul>$comment</ul>";
		} else {
			echo "</div><div class=\"main\"><div class=\"box warnbox\"><h3>$title</h3><ul>$comment</ul>";
		}

		if($quit) {
			echo '<br /><span class="red">'.lang('error_quit_msg').'</span><br /><br /><br />';
		}

		echo '<input type="button" class="btn oldbtn" onclick="history.back()" value="'.lang('click_to_back').'" />';

		echo '</div>';

		$quit && show_footer();
	}
}

function check_db($dbhost, $dbuser, $dbpw, $dbname, $tablepre) {
	if(!function_exists('mysqli_connect')) {
		show_msg('undefine_func', 'mysqli_connect', 0);
	}

	mysqli_report(MYSQLI_REPORT_OFF);

	$link = @new mysqli($dbhost, $dbuser, $dbpw);
	if($link->connect_errno) {
		$errno = $link->connect_errno;
		$error = $link->connect_error;
		if($errno == 1045) {
			show_msg('database_errno_1045', $error, 0);
		} elseif($errno == 2003) {
			show_msg('database_errno_2003', $error, 0);
		} else {
			show_msg('database_connect_error', $error, 0);
		}
		return false;
	} else {
		if($query = $link->query("SHOW TABLES FROM $dbname")) {
			if(!$query) {
				return false;
			}
			while($row = $query->fetch_row()) {
				if(preg_match("/^$tablepre/", $row[0])) {
					return false;
				}
			}
		}
	}
	return true;
}

function dirfile_check(&$dirfile_items) {
	foreach($dirfile_items as $key => $item) {
		$item_path = $item['path'];
		if($item['type'] == 'dir') {
			if(!dir_writeable(ROOT_PATH.$item_path)) {
				if(is_dir(ROOT_PATH.$item_path)) {
					$dirfile_items[$key]['status'] = 0;
					$dirfile_items[$key]['current'] = '+r';
				} else {
					$dirfile_items[$key]['status'] = -1;
					$dirfile_items[$key]['current'] = 'nodir';
				}
			} else {
				$dirfile_items[$key]['status'] = 1;
				$dirfile_items[$key]['current'] = '+r+w';
			}
		} else {
			if(file_exists(ROOT_PATH.$item_path)) {
				if(is_writable(ROOT_PATH.$item_path)) {
					$dirfile_items[$key]['status'] = 1;
					$dirfile_items[$key]['current'] = '+r+w';
				} else {
					$dirfile_items[$key]['status'] = 0;
					$dirfile_items[$key]['current'] = '+r';
				}
			} else {
				if(dir_writeable(dirname(ROOT_PATH.$item_path))) {
					$dirfile_items[$key]['status'] = 1;
					$dirfile_items[$key]['current'] = '+r+w';
				} else {
					$dirfile_items[$key]['status'] = -1;
					$dirfile_items[$key]['current'] = 'nofile';
				}
			}
		}
	}
}

function env_check(&$env_items) {
	foreach($env_items as $key => $item) {
		if($key == 'php') {
			$env_items[$key]['current'] = PHP_VERSION;
		} elseif($key == 'attachmentupload') {
			$env_items[$key]['current'] = @ini_get('file_uploads') ? getmaxupload() : 'unknow';
		} elseif($key == 'gdversion') {
			$tmp = function_exists('gd_info') ? gd_info() : array();
			$env_items[$key]['current'] = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
			unset($tmp);
		} elseif($key == 'diskspace') {
			if(function_exists('disk_free_space')) {
				$env_items[$key]['current'] = disk_free_space(ROOT_PATH);
			} else {
				$env_items[$key]['current'] = 'unknow';
			}
		} elseif(isset($item['c'])) {
			$env_items[$key]['current'] = constant($item['c']);
		} elseif($key == 'opcache') {
			$opcache_data = function_exists('opcache_get_configuration') ? opcache_get_configuration() : array();
			$env_items[$key]['current'] = !empty($opcache_data['directives']['opcache.enable']) ? 'enable' : 'disable';
		} elseif($key == 'curl') {
			if(function_exists('curl_init') && function_exists('curl_version')){
				$v = curl_version();
				$env_items[$key]['current'] = 'enable'.' '.$v['version'];
			}else{
				$env_items[$key]['current'] = 'disable';
			}
		} elseif(isset($item['f'])) {
			$env_items[$key]['current'] = function_exists($item['f']) ? 'enable' : 'disable';
		}

		$env_items[$key]['status'] = 1;
		if($item['r'] != 'notset' && strcmp($env_items[$key]['current'], $item['r']) < 0) {
			$env_items[$key]['status'] = 0;
		}
	}
}

function function_check(&$func_items) {
	foreach($func_items as $item) {
		function_exists($item) or show_msg('undefine_func', $item, 0);
	}
}

function dfloatval($int, $allowarray = false) {
	$ret = floatval($int);
	if($int == $ret || !$allowarray && is_array($int)) return $ret;
	if($allowarray && is_array($int)) {
		foreach($int as &$v) {
			$v = dfloatval($v, true);
		}
		return $int;
	} elseif($int <= 0xffffffff) {
		$l = strlen($int);
		$m = substr($int, 0, 1) == '-' ? 1 : 0;
		if(($l - $m) === strspn($int,'0987654321', $m)) {
			return $int;
		}
	}
	return $ret;
}
function show_env_result(&$env_items, &$dirfile_items, &$func_items, &$filesock_items) {

	$env_str = $file_str = $dir_str = $func_str = '';
	$error_code = 0;

	foreach($env_items as $key => $item) {
		if($key == 'php' && strcmp($item['current'], $item['r']) < 0) {
			show_msg('php_version_too_low', $item['current'], 0);
		}
		$status = 1;
		if($item['r'] != 'notset') {
			if(dfloatval($item['current']) && dfloatval($item['r'])) {
				if(dfloatval($item['current']) < dfloatval($item['r'])) {
					$status = 0;
					$error_code = ENV_CHECK_ERROR;
				}
			} else {
				if(strcmp($item['current'], $item['r']) < 0) {
					$status = 0;
					$error_code = ENV_CHECK_ERROR;
				}
			}
		}
		if($key == 'diskspace') {
			$item['current'] = format_space($item['current']);
			$item['r'] = format_space($item['r']);
		}
		if(VIEW_OFF) {
			$env_str .= "\t\t<runCondition name=\"$key\" status=\"$status\" Require=\"{$item['r']}\" Best=\"{$item['b']}\" Current=\"{$item['current']}\"/>\n";
		} else {
			$env_str .= '<tr'.($status ? '' : ' class="nwbg"').">\n";
			$env_str .= "<td>".lang($key)."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['r'])."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['b'])."</td>\n";
			$env_str .= ($status ? "<td class=\"w pdleft1\">" : "<td class=\"nw pdleft1\">").lang($item['current'])."</td>\n";
			$env_str .= "</tr>\n";
		}
	}

	foreach($dirfile_items as $key => $item) {
		$tagname = $item['type'] == 'file' ? 'File' : 'Dir';
		$variable = $item['type'].'_str';

		if(VIEW_OFF) {
			if($item['status'] == 0) {
				$error_code = ENV_CHECK_ERROR;
			}
			$$variable .= "\t\t\t<File name=\"{$item['path']}\" status=\"{$item['status']}\" requirePermisson=\"+r+w\" currentPermisson=\"{$item['current']}\" />\n";
		} else {
			$$variable .= '<tr'.($item['status'] == 1 ? '' : ' class="nwbg"').">\n";
			$$variable .= "<td>{$item['path']}</td><td class=\"w pdleft1\">".lang('writeable')."</td>\n";
			if($item['status'] == 1) {
				$$variable .= "<td class=\"w pdleft1\">".lang('writeable')."</td>\n";
			} elseif($item['status'] == -1) {
				$error_code = ENV_CHECK_ERROR;
				$$variable .= "<td class=\"nw pdleft1\">".lang('nodir')."</td>\n";
			} else {
				$error_code = ENV_CHECK_ERROR;
				$$variable .= "<td class=\"nw pdleft1\">".lang('unwriteable')."</td>\n";
			}
			$$variable .= "</tr>\n";
		}
	}

	if(VIEW_OFF) {

		$str = "<root>\n";
		$str .= "\t<runConditions>\n";
		$str .= $env_str;
		$str .= "\t</runConditions>\n";
		$str .= "\t<FileDirs>\n";
		$str .= "\t\t<Dirs>\n";
		$str .= $dir_str;
		$str .= "\t\t</Dirs>\n";
		$str .= "\t\t<Files>\n";
		$str .= $file_str;
		$str .= "\t\t</Files>\n";
		$str .= "\t</FileDirs>\n";
		$str .= "\t<error errorCode=\"$error_code\" errorMessage=\"\" />\n";
		$str .= "</root>";
		send_mime_type_header();
		echo $str;
		exit;

	} else {

		show_header();

		echo "<div class=\"box\"><h2 class=\"title\">".lang('env_check')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('project')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_required')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_best')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('curr_server')."</th>\n";
		echo "</tr>\n";
		echo $env_str;
		echo "</table></div>\n";

		echo "<div class=\"box\"><h2 class=\"title\">".lang('priv_check')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "\t<tr>\n";
		echo "\t<th>".lang('step1_file')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_need_status')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_status')."</th>\n";
		echo "</tr>\n";
		echo $file_str;
		echo $dir_str;
		echo "</table></div>\n";

		foreach($func_items as $item) {
			$status = function_exists($item);
			$func_str .= '<tr'.($status ? '' : ' class="nwbg"').">\n";
			$func_str .= "<td>$item()</td>\n";
			if($status) {
				$func_str .= "<td class=\"w pdleft1\">".lang('supportted')."</td>\n";
				$func_str .= "<td class=\"padleft\">".lang('none')."</td>\n";
			} else {
				$error_code = ENV_CHECK_ERROR;
				$func_str .= "<td class=\"nw pdleft1\">".lang('unsupportted')."</td>\n";
				$func_str .= "<td><font color=\"red\">".lang('advice_'.$item)."</font></td>\n";
			}
		}
		$func_strextra = '';
		$filesock_disabled = 0;
		foreach($filesock_items as $item) {
			$status = function_exists($item);
			$func_strextra .= '<tr'.($status ? '' : ' class="nwbg"').">\n";
			$func_strextra .= "<td>$item()</td>\n";
			if($status) {
				$func_strextra .= "<td class=\"w pdleft1\">".lang('supportted')."</td>\n";
				$func_strextra .= "<td class=\"padleft\">".lang('none')."</td>\n";
				break;
			} else {
				$filesock_disabled++;
				$func_strextra .= "<td class=\"nw pdleft1\">".lang('unsupportted')."</td>\n";
				$func_strextra .= "<td><font color=\"red\">".lang('advice_'.$item)."</font></td>\n";
			}
		}
		if($filesock_disabled == count($filesock_items)) {
			$error_code = ENV_CHECK_ERROR;
		}
		echo "<div class=\"box\"><h2 class=\"title\">".lang('func_depend')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('func_name')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('check_result')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('suggestion')."</th>\n";
		echo "</tr>\n";
		echo $func_str.$func_strextra;
		echo "</table></div>\n";

		echo <<<EOT
<script>
	document.querySelectorAll('.box').forEach(function(elem){
		if(!elem.querySelector('.nw')) {
			elem.classList.add('valid','collapse');
			elem.addEventListener('click',function(){
				this.classList.contains('collapse') ? this.classList.remove('collapse') : this.classList.add('collapse');
			});
		}
	});
</script>
EOT;

		show_next_step(2, $error_code);

		show_footer();

	}

}

function show_next_step($step, $error_code) {
	global $uchidden;

	if(!empty($uchidden)) {
		$uc_info_transfer = unserialize(urldecode($uchidden));
		if(!isset($uc_info_transfer['ucapi']) && !isset($uc_info_transfer['ucfounderpw'])){
			$uchidden = '';
		} else {
			$uchidden = dhtmlspecialchars($uchidden);
		}
	}

	echo "<form action=\"index.php\" method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"step\" value=\"$step\" />";
	if(isset($GLOBALS['hidden'])) {
		echo $GLOBALS['hidden'];
	}
	echo "<input type=\"hidden\" name=\"uchidden\" value=\"$uchidden\" />";
	if($uchidden) {
		echo "<input type=\"hidden\" name=\"install_ucenter\" value=\"no\" />";
	}
	if($error_code == 0) {
		$nextstep = "<input type=\"button\" class=\"btn oldbtn\" onclick=\"history.back();\" value=\"".lang('old_step')."\"><input type=\"submit\" class=\"btn\" value=\"".lang('new_step')."\">\n";
	} else {
		$nextstep = "<input type=\"button\" class=\"btn\" disabled=\"disabled\" value=\"".lang('not_continue')."\">\n";
	}
	echo "<div class=\"btnbox\"><div class=\"inputbox\">".$nextstep."</div></div>\n";
	echo "</form>\n";
}

function show_form(&$form_items, $error_msg) {

	global $step, $uchidden;

	if(empty($form_items) || !is_array($form_items)) {
		return;
	}

	show_header();
	show_setting('start');
	show_setting('hidden', 'step', $step);
	if($step == 2) {
		echo '<div class="box">';
		show_tips('install_dzstandalone');
		show_tips('install_dzfull');
		show_tips('install_dzonly');
		echo '</div>';
	} else {
		show_setting('hidden', 'install_ucenter', getgpc('install_ucenter'));
	}
	$is_first = 1;
	if(!empty($uchidden)) {
		$uc_info_transfer = unserialize(urldecode($uchidden));
	}
	echo '<div id="form_items_'.$step.'" '.($step == 2 && !getgpc('install_ucenter') ? 'style="display:none"' : '').'>';
	foreach($form_items as $key => $items) {
		global ${'error_'.$key};
		if($is_first == 0) {
			echo '</div>';
		}

		if(!${'error_'.$key}) {
			show_tips('tips_'.$key);
		} else {
			show_error('tips_admin_config', ${'error_'.$key});
		}

		echo '<div class="box">';
		foreach($items as $k => $v) {
			$value = '';
			if(!empty($error_msg)) {
				$value = isset($_POST[$key][$k]) ? $_POST[$key][$k] : '';
			}
			if(empty($value)) {
				if(isset($v['value']) && is_array($v['value'])) {
					if($v['value']['type'] == 'constant') {
						$value = defined($v['value']['var']) ? constant($v['value']['var']) : $v['value']['var'];
					} else {
						$value = $GLOBALS[$v['value']['var']];
					}
				} else {
					$value = '';
				}
			}

			if($k == 'ucurl' && isset($uc_info_transfer['ucapi'])) {
				$value = $uc_info_transfer['ucapi'];
			} elseif($k == 'ucpw' && isset($uc_info_transfer['ucfounderpw'])) {
				$value = $uc_info_transfer['ucfounderpw'];
			} elseif($k == 'ucip') {
				$value = '';
			}

			show_setting($k, $key.'['.$k.']', $value, $v['type'], isset($error_msg[$key][$k]) ? $key.'_'.$k.'_invalid' : '');
		}

		if($is_first) {
			$is_first = 0;
		}
	}
	echo '</div>';
	echo '</div>';
	echo '<div class="btnbox">';
	show_setting('', 'submitname', 'new_step', ($step == 2 ? 'submit|oldbtn' : 'submit' ));
	echo '</div>';
	show_setting('end');
	show_footer();
}

function dunserialize($data) {
	if(($ret = unserialize($data)) === false) {
		$ret = unserialize(stripslashes($data));
	}
	return $ret;
}

function cloudaddons_getversion($instid) {
	$timestamp = time();
	$data = 'product=discuzx&sitever='.DISCUZ_VERSION.'/'.DISCUZ_RELEASE.'&sitecharset='.CHARSET.'&addonversion=1&os='.PHP_OS .'&php='.PHP_VERSION.'&web='.$_SERVER['SERVER_SOFTWARE'].'&lang='.INSTALL_LANG.'&type=installer&instid='.$instid;
	$param = 'data='.rawurlencode(base64_encode($data));
	$param .= '&md5hash='.substr(md5($data.$timestamp), 8, 8).'&timestamp='.$timestamp;
	$param .= '&mod=app&ac=installcheck';

	$url = 'https://addon.dismall.com/index.php?'.$param;

	$return = dfopen($url, 0, '', '', FALSE, '', 3);

	if(!empty($return)) {
		$ret = dunserialize($return);
		if(is_array($ret) && isset($ret['is_latest']) && !$ret['is_latest']) {
			return $ret;
		} else {
			return array('is_latest' => 1, 'url' => '');
		}
	} else {
		return array('is_latest' => 1, 'url' => '');
	}
}

function show_license() {
	global $self, $uchidden, $step, $instid;
	$next = $step + 1;
	if(VIEW_OFF) {

		show_msg('license_contents', lang('license'), 1);

	} else {

		show_header();

		$license = str_replace('  ', '&nbsp; ', lang('license'));
		$lang_agreement_yes = lang('agreement_yes');
		$lang_agreement_no = lang('agreement_no');

		$lang_php8 = lang('php8_tips');
		$lang_noutf8 = lang('no_utf8_tips').lang('next_tips');
		$lang_unstable = lang('unstable_tips').lang('next_tips');

		$is_php8 = version_compare(PHP_VERSION, '9.0.0', '>=') ? 1 : 0;
		$is_utf8 = (strtolower(CHARSET) == 'utf-8') ? 1 : 0;
		$is_unstable = (strlen(DISCUZ_RELEASE) != 8 || DISCUZ_RELEASE == 20180101) ? 1 : 0;

		$info = cloudaddons_getversion($instid);

		$hrefurl = empty($info['url']) ? 'https://gitee.com/Discuz/DiscuzX/releases' : $info['url'];

		if($info['is_latest']) {
			$is_latest = 1;
			$lang_nolatest = lang('no_latest_tips').lang('next_tips');
		} else {
			$is_latest = 0;
			$lang_nolatest = empty($info['tips']) ? (lang('no_latest_tips').lang('next_tips')) : ($info['tips'].lang('next_tips'));
		}

		echo <<<EOT
</div>
<div class="main">
	<div class="licenseblock">$license</div>
	<div class="btnbox">
		<form method="get" autocomplete="off" action="index.php" class="inputbox">
		<input type="hidden" name="step" value="$next">
		<input type="hidden" name="uchidden" value="$uchidden">
		<input type="button" class="btn oldbtn" name="exit" value="{$lang_agreement_no}"  onclick="window.close(); return false;">
		<input type="submit" class="btn" name="submit" value="{$lang_agreement_yes}" onclick="return checker();">
		</form>
	</div>
	<script type="text/javascript">
	function checker() {
		if(!$is_latest && confirm("$lang_nolatest")) {
			window.location.href = "$hrefurl";
			return false;
		}
		if($is_php8) {
			alert("$lang_php8");
			return false;
		}
		if($is_unstable && confirm("$lang_unstable")) {
			window.location.href = "$hrefurl";
			return false;
		}
		if(!$is_utf8 && confirm("$lang_noutf8")) {
			window.location.href = "$hrefurl";
			return false;
		}
		return true;
	}
	</script>
EOT;

		show_footer();

	}
}

function transfer_ucinfo(&$post) {
	global $uchidden;
	if(isset($post['ucapi']) && isset($post['ucfounderpw'])) {
		$arr = array(
			'ucapi' => $post['ucapi'],
			'ucfounderpw' => $post['ucfounderpw']
			);
		$uchidden = urlencode(serialize($arr));
	} else {
		$uchidden = '';
	}
}

function createtable($sql, $dbver) {
	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
	$type = in_array($type, array('INNODB', 'MYISAM', 'HEAP', 'MEMORY')) ? $type : 'INNODB';
	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql) .
		" ENGINE=$type DEFAULT CHARSET=" . DBCHARSET .
		(DBCHARSET === 'utf8mb4' ? " COLLATE=utf8mb4_unicode_ci" : "");
}

function dir_writeable($dir) {
	$writeable = 0;
	if(!is_dir($dir)) {
		@mkdir($dir, 0777);
	}
	if(is_dir($dir)) {
		if($fp = @fopen("$dir/test.txt", 'w')) {
			@fclose($fp);
			@unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $writeable;
}

function dir_clear($dir) {
	global $lang;
	showjsmessage($lang['clear_dir'] . ' ' . str_replace(ROOT_PATH, '', $dir) . "\n");
	if($directory = @dir($dir)) {
		while($entry = $directory->read()) {
			$filename = $dir.'/'.$entry;
			if(is_file($filename)) {
				@unlink($filename);
			}
		}
		$directory->close();
		@touch($dir.'/index.htm');
	}
}

function show_header() {
	define('SHOW_HEADER', TRUE);
	global $step;
	$version = DISCUZ_VERSION;
	$release = DISCUZ_RELEASE;
	$install_lang = lang(INSTALL_LANG);
	$title = lang('title_install');
	$titlehtml = '<span>'.SOFT_NAME.'</span>'.lang('install_wizard');
	$nostep = $step > 0 ? '' : ' nostep';
	$charset = CHARSET;
	$reldisp = is_numeric(DISCUZ_RELEASE) ? ('Release ' . DISCUZ_RELEASE) : DISCUZ_RELEASE;
	echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="$charset" />
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>$title</title>
<link rel="stylesheet" href="static/style.css" type="text/css" media="all" />
<script type="text/javascript">
	function $(id) {
		return document.getElementById(id);
	}

	function showmessage(message) {
		document.getElementById('notice').innerHTML += message + '<br />';
	}
</script>
<meta content="Comsenz Inc." name="Copyright" />
</head>
<body>
<div class="container{$nostep}">
	<div class="header">
		<h1>$titlehtml</h1>
		<span>Discuz! $version $install_lang $reldisp</span>
EOT;

	$step > 0 && show_step($step);
	echo "\r\n";
	echo str_repeat('  ', 1024 * 4);
	echo "\r\n";
	flush();
	ob_flush();
}

function show_footer($quit = true) {

	$copy = lang('copyright');

	echo <<<EOT
		<div class="footer">$copy</div>
	</div>
</div>
</body>
</html>
EOT;
	$quit && exit();
}

function showjsmessage($message) {
	if(VIEW_OFF) return;
	append_to_install_log_file($message);
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	if($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function secrandom($length, $numeric = 0, $strong = false) {
	// Thank you @popcorner for your strong support for the enhanced security of the function.
	$chars = $numeric ? array('A','B','+','/','=') : array('+','/','=');
	$num_find = str_split('CDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
	$num_repl = str_split('01234567890123456789012345678901234567890123456789');
	$isstrong = false;
	if(function_exists('random_bytes')) {
		$isstrong = true;
		$random_bytes = function($length) {
			return random_bytes($length);
		};
	} elseif(extension_loaded('mcrypt') && function_exists('mcrypt_create_iv')) {
		// for lower than PHP 7.0, Please Upgrade ASAP.
		$isstrong = true;
		$random_bytes = function($length) {
			$rand = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
			if ($rand !== false && strlen($rand) === $length) {
				return $rand;
			} else {
				return false;
			}
		};
	} elseif(extension_loaded('openssl') && function_exists('openssl_random_pseudo_bytes')) {
		// for lower than PHP 7.0, Please Upgrade ASAP.
		// openssl_random_pseudo_bytes() does not appear to cryptographically secure
		// https://github.com/paragonie/random_compat/issues/5
		$isstrong = true;
		$random_bytes = function($length) {
			$rand = openssl_random_pseudo_bytes($length, $secure);
			if($secure === true) {
				return $rand;
			} else {
				return false;
			}
		};
	}
	if(!$isstrong) {
		return $strong ? false : random($length, $numeric);
	}
	$retry_times = 0;
	$return = '';
	while($retry_times < 128) {
		$getlen = $length - strlen($return); // 33% extra bytes
		$bytes = $random_bytes(max($getlen, 12));
		if($bytes === false) {
			return false;
		}
		$bytes = str_replace($chars, '', base64_encode($bytes));
		$return .= substr($bytes, 0, $getlen);
		if(strlen($return) == $length) {
			return $numeric ? str_replace($num_find, $num_repl, $return) : $return;
		}
		$retry_times++;
	}
}

function redirect($url) {

	echo "<script>".
	"function redirect() {window.location.replace('$url');}\n".
	"setTimeout('redirect();', 0);\n".
	"</script>";
	exit();

}

function validate_ip($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function get_onlineip() {
	$onlineip = $_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
		$onlineip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") > 0) {
			$exp = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			$onlineip = validate_ip(trim($exp[0])) ? $exp[0] : $onlineip;
		} else {
			$onlineip = validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $onlineip;
		}
	}
	return $onlineip;
}

function timezone_set($timeoffset = 8) {
	if(function_exists('date_default_timezone_set')) {
		@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
	}
}

function save_config_file($filename, $config, $default) {
	$config = setdefault($config, $default);
	$date = gmdate("Y-m-d H:i:s", time() + 3600 * 8);
	$content = <<<EOT
<?php


\$_config = array();

EOT;
	$content .= getvars(array('_config' => $config));
	$content .= "\r\n// ".str_pad('  THE END  ', 50, '-', STR_PAD_BOTH)." //\r\n\r\n?>";
	file_put_contents($filename, $content);
}

function setdefault($var, $default) {
	foreach ($default as $k => $v) {
		if(!isset($var[$k])) {
			$var[$k] = $default[$k];
		} elseif(is_array($v)) {
			$var[$k] = setdefault($var[$k], $default[$k]);
		}
	}
	return $var;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	// 动态密钥长度, 通过动态密钥可以让相同的 string 和 key 生成不同的密文, 提高安全性
	$ckey_length = 4;

	$key = md5($key ? $key : UC_KEY);
	// a参与加解密, b参与数据验证, c进行密文随机变换
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	// 参与运算的密钥组
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	// 前 10 位用于保存时间戳验证数据有效性, 10 - 26位保存 $keyb , 解密时通过其验证数据完整性
	// 如果是解码的话会从第 $ckey_length 位开始, 因为密文前 $ckey_length 位保存动态密匙以保证解密正确
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	// 产生密钥簿
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	// 打乱密钥簿, 增加随机性
	// 类似 AES 算法中的 SubBytes 步骤
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	// 从密钥簿得出密钥进行异或，再转成字符 
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		// 这里按照算法对数据进行验证, 保证数据有效性和完整性
		// $result 01 - 10 位是时间, 如果小于当前时间或为 0 则通过
		// $result 10 - 26 位是加密时的 $keyb , 需要和入参的 $keyb 做比对
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) === substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密钥保存在密文里, 并用 base64 编码保证传输时不被破坏
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function show_db_install() {
	if(VIEW_OFF) return;
	global $dbhost, $dbuser, $dbpw, $dbname, $tablepre, $username, $password, $email, $uid, $myisam2innodb;
	$dzucfull = DZUCFULL;
	$dzucstl = DZUCSTL ? 1 : 0;
	$succlang = $myisam2innodb ? 'initdbinnodbresult_succ' : 'initdbdataresult_succ';
	$allinfo = base64_encode(serialize(compact('dbhost', 'dbuser', 'dbpw', 'dbname', 'tablepre', 'username', 'password', 'email', 'dzucfull', 'dzucstl', 'uid', 'myisam2innodb')));
	init_install_log_file();
?>
		<script type="text/javascript">
			var ajax = {};
			ajax.x = function () {
				if(typeof XMLHttpRequest !== 'undefined') {
					return new XMLHttpRequest();
				}
				var versions = ["MSXML2.XmlHttp.6.0", "MSXML2.XmlHttp.5.0", "MSXML2.XmlHttp.4.0", "MSXML2.XmlHttp.3.0", "MSXML2.XmlHttp.2.0", "Microsoft.XmlHttp"];
				var xhr;
				for(var i = 0; i < versions.length; i++) {
					try {
						xhr = new ActiveXObject(versions[i]);
						break;
					} catch (e) {
					}
				}
				return xhr;
			};

			ajax.send = function (url, callback, method, data, async) {
				if(async === undefined) {
					async = true;
				}
				var x = ajax.x();
				x.open(method, url, async);
				x.onreadystatechange = function () {
					if((x.readyState == 4) && (typeof callback == 'function')) {
						callback(x.responseText, x.status);
					}
				};
				if(method == 'POST') {
					x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				}
				x.send(data);
			};

			ajax.get = function (url, callback) {
				ajax.send(url, callback, 'GET', null, true);
			};

			function set_notice(str) {
				document.getElementById('notice').innerHTML = str;
				document.getElementById('notice').scrollTop = 100000000;
			}

			function append_notice(str) {
				document.getElementById('notice').innerHTML += str;
				document.getElementById('notice').scrollTop = 100000000;
			}

			function refresh_lastmsg() {
				document.getElementById('lastmsg').innerHTML = document.querySelector('#notice>p:last-child').outerHTML;
			}

			function add_instfail() {
				document.querySelector('.box').classList.add('instfail');
				document.getElementById('notice').scrollTop = 100000000;
			}

			function refresh_progress() {
				// 进度条的总数，需要跟进实际安装情况修改
				var total = 333;
				var percent = document.querySelectorAll('#notice>p').length * 95 / total;
				percent = (percent > 95) ? 95 : percent;
				document.getElementById('pgb').style.width = percent + '%';
			}

			function init_bind() {
				document.querySelector(".progress").addEventListener("click", function () {
					var a = document.getElementById("notice");
					a.style.display = "block" == a.style.display ? "" : "block";
				});
			}

			function initinput() {
				window.location='<?php echo 'index.php?step='.($GLOBALS['step']);?>';
			}

			var old_log_data = '';
			var log_offset = 0;
			var stuck_times = 0;

			function request_do_db_init() {
				// 发起数据库初始化请求
				ajax.get('index.php?<?= http_build_query(array('method' => 'do_db_init', 'allinfo' => $allinfo)) ?>', function(data) {
					if(data.indexOf('Discuz! Database Error') !== -1 || data.indexOf('Discuz! System Error') !== -1 || data.indexOf('Fatal error') !== -1) {
						var p = document.createElement('p');
						p.innerHTML = '<?= lang('failed') ?> ' + data;
						p.className = 'red';
						append_notice(p.outerHTML);
						append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
						document.getElementById('laststep').value = '<?= lang('error_reinstall_msg') ?>';
						add_instfail();
						return;
					}
					// 失败时不继续请求后续操作
					var resultDiv = document.getElementById('notice');
					if(resultDiv.innerHTML.indexOf('<?= lang('failed') ?>') === -1) {
						// 数据库表创建请求完成拉起数据库数据初始化
						request_do_db_data_init();
					}
				});
			}

			function request_do_db_data_init() {
				ajax.get('index.php?<?= http_build_query(array('method' => 'do_db_data_init', 'allinfo' => $allinfo)) ?>', function(data) {
					if(data.indexOf('Discuz! Database Error') !== -1 || data.indexOf('Discuz! System Error') !== -1 || data.indexOf('Fatal error') !== -1) {
						var p = document.createElement('p');
						p.innerHTML = '<?= lang('failed') ?> ' + data;
						p.className = 'red';
						append_notice(p.outerHTML);
						append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
						document.getElementById('laststep').value = '<?= lang('error_reinstall_msg') ?>';
						add_instfail();
						return;
					}
					var resultDiv = document.getElementById('notice');
					if(resultDiv.innerHTML.indexOf('<?= lang('failed') ?>') === -1) {
						<?php echo $myisam2innodb ? 'request_do_db_innodb(0);' : 'request_do_initsys();';?>
					}
				});
			}

			function request_do_db_innodb(i) {
				ajax.get('index.php?<?= http_build_query(array('method' => 'do_db_innodb', 'allinfo' => $allinfo)) ?>&i=' + i, function(data) {
					if(data.indexOf('Discuz! Database Error') !== -1 || data.indexOf('Discuz! System Error') !== -1 || data.indexOf('Fatal error') !== -1) {
						var p = document.createElement('p');
						p.innerHTML = '<?= lang('failed') ?> ' + data;
						p.className = 'red';
						append_notice(p.outerHTML);
						append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
						add_instfail();
						return;
					}
					var resultDiv = document.getElementById('notice');
					if(resultDiv.innerHTML.indexOf('<?= lang('failed') ?>') === -1) {
						if(resultDiv.innerHTML.indexOf('<?= lang('initdbinnodbresult_succ') ?>') !== -1) {
							// 拉起系统初始化
							request_do_initsys();
						} else if(document.getElementById('laststep').value.indexOf('<?= lang('install_in_processed') ?>') !== -1) {
							// 循环完成InnoDB转换
							request_do_db_innodb(Number(i)+1);
						}
					}
				});
			}

			function request_log() {
				var timest = new Date().getTime().toString().substring(5);
				ajax.get('index.php?method=check_db_init_progress&timestamp=' + timest + '&offset=' + log_offset, function (data, status) {
					// 新增对于 >= 400 状态的判断, 避免被服务器自带安全软件或者 CDN 拉黑地址之后不报错
					if(status >= 400) {
						append_notice('<p class="red">HTTP '+status+' <?= lang('failed') ?></p>');
						append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
						add_instfail();
						return;
					}
					log_offset = parseInt(data.substring(0,5));
					data = data.substring(5);
					if(stuck_times >= 120) {
						// 如果安装程序两分钟没有响应, 则提示安装可能卡死
						stuck_times = 0;
						append_notice('<p class="red"><?= lang('error_stuck_msg') ?></p>');
						setTimeout(request_log, 1000);
						return;
					}
					if(data === old_log_data && stuck_times < 120) {
						stuck_times++;
						setTimeout(request_log, 1000);
						return;
					}
					stuck_times = 0;
					old_log_data = data;
					append_notice(
						data.trim().split("\n").map(function(l) {
							if(l.indexOf('<?= lang('failed') ?>') !== -1) {
								return '<p class="red">' + l + '</p>';
							} else if(!l) {
								return '';
							} else {
								return '<p>' + l + '</p>';
							}
						}).join('')
					);
					refresh_lastmsg();
					refresh_progress();
					if(data.indexOf('<?= lang('failed') ?>') !== -1) {
						append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
						add_instfail();
						return;
					}
					if(data.indexOf('<?= lang($succlang) ?>') === -1) {
						setTimeout(request_log, 200);
					}
				});
			}

			function request_do_initsys() {
				var resultDiv = document.getElementById('notice');
				// 数据库初始化失败不进行系统初始化
				if(resultDiv.innerHTML.indexOf('<?= lang('failed') ?>') !== -1) {
					if(document.getElementById('laststep').value.indexOf('<?= lang('error_reinstall_msg') ?>') === -1) {
						document.getElementById('laststep').value = '<?= lang('error_quit_msg') ?>';
					}
					return;
				}
				if(resultDiv.innerHTML.indexOf('<?= lang($succlang) ?>') !== -1) {
					// 数据库初始化成功就进行系统初始化
					append_notice("<p><?= lang('initsys') ?> ... </p>");
					refresh_lastmsg();
					ajax.get('../misc.php?mod=initsys', function(callback, status) {
						if(status >= 400 || callback.indexOf('Access Denied') !== -1 || callback.indexOf('Discuz! Database Error') !== -1 || callback.indexOf('Discuz! System Error') !== -1 || callback.indexOf('Fatal error') !== -1) {
							var p = document.createElement('p');
							p.className = 'red';
							if(status >= 400) {
								p.innerText = 'HTTP '+status+' <?= lang('failed') ?> ' + callback;
							} else {
								p.innerText = '<?= lang('failed') ?> ' + callback;
							}
							append_notice(p.outerHTML);
							append_notice('<p class="red"><?= lang('error_quit_msg') ?></p>');
							document.getElementById('laststep').value = '<?= lang('error_quit_msg') ?>';
							add_instfail();
							return;
						}
						append_notice('<p><?= lang('initsys').lang('succeed') ?></p>');
						refresh_lastmsg();
						document.getElementById('pgb').style.width = '100%';
						document.getElementById('pgb').className = '';
						document.getElementById('laststep').value = '<?= lang('succeed') ?>';
						document.getElementById('laststep').disabled = false;
						window.setTimeout(function() {
							window.location='index.php?method=ext_info';
						}, 1000);
					});
				} else {
					// 数据库初始化状态未知时不做初始化, 一秒钟后重新判断数据库初始化状态
					setTimeout(request_do_initsys, 1000);
				}
			}

			window.onload = function() {
				request_do_db_init();
				init_bind();
				setTimeout(request_log, 500);
			}
		</script>
		<div class="box"><div class="desc" id="lastmsg"><?= lang('install_in_processed') ?></div><div class="progress"><div class="move" id="pgb"></div></div><div id="notice"></div></div>
		<div class="btnbox">
			<input type="button" class="btn" name="submit" value="<?php echo lang('install_in_processed');?>" disabled="disabled" id="laststep" onclick="initinput()">
		</div>
<?php
}

function runquery($sql) {
	global $lang, $tablepre, $db;

	if(!isset($sql) || empty($sql)) return;

	$sql = str_replace("\r", "\n", str_replace(' '.ORIG_TABLEPRE, ' '.$tablepre, $sql));
	$sql = str_replace("\r", "\n", str_replace(' `'.ORIG_TABLEPRE, ' `'.$tablepre, $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	$oldtablename = "";
	foreach($ret as $query) {
		$query = trim($query);
		if($query) {
			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				if ($db->query(createtable($query, $db->version()))) {
					showjsmessage(lang('create_table').' '.$name.'  ... '.lang('succeed') . "\n");
				} else {
					showjsmessage(lang('create_table').' '.$name.'  ... '.lang('failed') . "\n");
					return false;
				}
			} elseif(substr($query, 0, 6) == 'INSERT') {
				$name = preg_replace("/INSERT\s+INTO\s+[\`]?([a-z0-9_]+)[\`]? .*/is", "\\1", $query);
				if ($db->query($query)) {
					if($oldtablename != $name) {
						showjsmessage(lang('init_table_data').' '.$name.'  ... '.lang('succeed') . "\n");
						$oldtablename = $name;
					}
				} else {
					showjsmessage(lang('init_table_data').' '.$name.'  ... '.lang('failed') . "\n");
					return false;
				}
			}else{
				if (!$db->query($query)) {
					showjsmessage(lang('failed') . "\n");
					return false;
				}
			}

		}
	}
	return true;
}

function runucquery($sql, $tablepre) {
	global $lang, $db;

	if(!isset($sql) || empty($sql)) return;

	$sql = str_replace("\r", "\n", str_replace(' uc_', ' '.$tablepre, $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {
			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				if($db->query(createtable($query, $db->version()))) {
					showjsmessage(lang('create_table').' '.$name.' ... '.lang('succeed') . "\n");
				} else {
					showjsmessage(lang('create_table').' '.$name.' ... '.lang('failed') . "\n");
					return false;
				}
			} else {
				if (!$db->query($query)) {
					showjsmessage(lang('failed') . "\n");
					return false;
				}
			}

		}
	}
	return true;
}


function charcovert($string) {
	return str_replace('\'', '\\\'', $string);
}

function insertconfig($s, $find, $replace) {
	if(preg_match($find, $s)) {
		$s = preg_replace($find, $replace, $s);
	} else {
		$s .= "\r\n".$replace;
	}
	return $s;
}

function getgpc($k, $t='GP') {
	$t = strtoupper($t);
	switch($t) {
		case 'GP' : isset($_POST[$k]) ? $var = &$_POST : $var = &$_GET; break;
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	return isset($var[$k]) ? $var[$k] : null;
}

function var_to_hidden($k, $v) {
	return "<input type=\"hidden\" name=\"$k\" value=\"$v\" />\n";
}

function fsocketopen($hostname, $port = 80, &$errno = null, &$errstr = null, $timeout = 15) {
	$fp = '';
	if(function_exists('fsockopen')) {
		$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
	} elseif(function_exists('pfsockopen')) {
		$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
	} elseif(function_exists('stream_socket_client')) {
		$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
	}
	return $fp;
}

function dfopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype  = 'URLENCODE', $allowcurl = TRUE) {
	$return = '';
	$matches = parse_url($url);
	$scheme = strtolower($matches['scheme']);
	$host = $matches['host'];
	$path = !empty($matches['path']) ? $matches['path'].(!empty($matches['query']) ? '?'.$matches['query'] : '') : '/';
	$port = !empty($matches['port']) ? $matches['port'] : ($scheme == 'https' ? 443 : 80);

	if(function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
		$ch = curl_init();
		$ip && curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: ".$host));
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		// 在提供 IP 地址的同时, 当请求主机名并非一个合法 IP 地址, 且 PHP 版本 >= 5.5.0 时, 使用 CURLOPT_RESOLVE 设置固定的 IP 地址与域名关系
		// 在不支持的 PHP 版本下, 继续采用原有不支持 SNI 的流程
		if(!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP) && version_compare(PHP_VERSION, '5.5.0', 'ge')) {
			curl_setopt($ch, CURLOPT_RESOLVE, array("$host:$port:$ip"));
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.$host.':'.$port.$path);
		} else {
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.($ip ? $ip : $host).':'.$port.$path);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if($encodetype == 'URLENCODE') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			} else {
				parse_str($post, $postarray);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray);
			}
		}
		if($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		$status = curl_getinfo($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if($errno || $status['http_code'] != 200) {
			return;
		} else {
			return !$limit ? $data : substr($data, 0, $limit);
		}
	}

	if($post) {
		$out = "POST $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		if($allowcurl) {
			$encodetype = 'URLENCODE';
		}
		$boundary = $encodetype == 'URLENCODE' ? '' : '; boundary='.trim(substr(trim($post), 2, strpos(trim($post), "\n") - 2));
		$header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data$boundary\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= 'Content-Length: '.strlen($post)."\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cache-Control: no-cache\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header.$post;
	} else {
		$out = "GET $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header;
	}

	$fpflag = 0;
	$context = array();
	if($scheme == 'https') {
		$context['ssl'] = array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'peer_name' => $host
		);
		if(version_compare(PHP_VERSION, '5.6.0', '<')) {
			$context['ssl']['SNI_enabled'] = true;
			$context['ssl']['SNI_server_name'] = $host;
		}
	}
	if(ini_get('allow_url_fopen')) {
		$context['http'] = array(
			'method' => $post ? 'POST' : 'GET',
			'header' => $header,
			'timeout' => $timeout
		);
		if($post) {
			$context['http']['content'] = $post;
		}
		$context = stream_context_create($context);
		$fp = @fopen($scheme.'://'.($ip ? $ip : $host).':'.$port.$path, 'b', false, $context);
		$fpflag = 1;
	} elseif(function_exists('stream_socket_client')) {
		$context = stream_context_create($context);
		$fp = @stream_socket_client(($scheme == 'https' ? 'ssl://' : '').($ip ? $ip : $host).':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
	} else {
		$fp = @fsocketopen(($scheme == 'https' ? 'ssl://' : '').($scheme == 'https' ? $host : ($ip ? $ip : $host)), $port, $errno, $errstr, $timeout);
	}

	if(!$fp) {
		return '';
	} else {
		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		if(!$fpflag) {
			@fwrite($fp, $out);
		}
		$status = stream_get_meta_data($fp);
		if(!$status['timed_out']) {
			while (!feof($fp) && !$fpflag) {
				if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
					break;
				}
			}

			$stop = false;
			while(!feof($fp) && !$stop) {
				$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
				$return .= $data;
				if($limit) {
					$limit -= strlen($data);
					$stop = $limit <= 0;
				}
			}
		}
		@fclose($fp);
		return $return;
	}
}

function check_env() {

	global $lang, $attachdir;

	$errors = array('quit' => false);
	$quit = false;

	if(!function_exists('mysqli_connect')) {
		$errors[] = 'mysqli_unsupport';
		$quit = true;
	}

	if(!file_exists(ROOT_PATH.'./config.inc.php')) {
		$errors[] = 'config_nonexistence';
		$quit = true;
	} elseif(!is_writeable(ROOT_PATH.'./config.inc.php')) {
		$errors[] = 'config_unwriteable';
		$quit = true;
	}

	$checkdirarray = array(
		'attach' => $attachdir,
		'forumdata' => './forumdata',
		'cache' => './forumdata/cache',
		'ftemplates' => './forumdata/templates',
		'threadcache' => './forumdata/threadcaches',
		'log' => './forumdata/logs',
		'uccache' => './uc_client/data/cache'
	);

	foreach($checkdirarray as $key => $dir) {
		if(!dir_writeable(ROOT_PATH.$dir)) {
			$langkey = $key.'_unwriteable';
			$errors[] = $key.'_unwriteable';
			if(!in_array($key, array('ftemplate'))) {
				$quit = TRUE;
			}
		}
	}

	$errors['quit'] = $quit;
	return $errors;

}

function show_error($type, $errors = '', $quit = false) {

	global $lang, $step;

	$title = lang($type);
	$comment = lang($type.'_comment', false);
	$errormsg = '';
	if($errors) {
		if(!empty($errors)) {
			foreach ((array)$errors as $k => $v) {
				if(is_numeric($k)) {
					$comment .= "<li><em class=\"red\">".lang($v)."</em></li>";
				}
			}
		}
	}

	if($step > 0) {
		echo "<div class=\"desc\"><b>$title</b><ul>$comment</ul>";
	} else {
		echo "<div><b>$title</b><ul style=\"line-height: 200%; margin-left: 30px;\">$comment</ul>";
	}

	if($quit) {
		echo '<br /><span class="red">'.$lang['error_quit_msg'].'</span><br /><br /><br /><br /><br /><br />';
	}

	echo '</div>';

	$quit && show_footer();
}

function show_tips($tip, $title = '', $comment = '', $style = 1) {
	global $lang;
	$title = empty($title) ? lang($tip) : $title;
	$comment = empty($comment) ? lang($tip.'_comment', FALSE) : $comment;
	if($style) {
		echo "<div class=\"desc\">$title";
	} else {
		echo "</div><div class=\"main\">$title<div class=\"desc1 marginbot\"><ul>";
	}
	$comment && print('<div class="comm">'.$comment.'</div>');
	echo "</div>";
}

function show_setting($setname, $varname = '', $value = '', $type = 'text|password|checkbox', $error = '') {
	if($setname == 'start') {
		echo "<form method=\"post\" action=\"index.php\">\n";
		return;
	} elseif($setname == 'end') {
		echo "\n</form>\n";
		return;
	} elseif($setname == 'hidden') {
		echo "<input type=\"hidden\" name=\"$varname\" value=\"$value\">\n";
		return;
	}

	echo "\n".'<div class="inputbox'.($error ? ' red' : '').'">';
	if($type == 'text' || $type == 'password') {
		echo "\n".'<label class="tbopt" for="inst_'.$varname.'">'.(empty($setname) ? '' : lang($setname).':')."</label>\n";
		$value = dhtmlspecialchars($value);
		echo "<input type=\"$type\" id=\"inst_{$varname}\" name=\"$varname\" value=\"$value\" class=\"txt\">";
	} elseif(strpos($type, 'submit') !== FALSE) {
		if(strpos($type, 'oldbtn') !== FALSE) {
			echo "<input type=\"button\" name=\"oldbtn\" value=\"".lang('old_step')."\" class=\"btn oldbtn\" onclick=\"history.back();\">\n";
		}
		$value = empty($value) ? 'new_step' : $value;
		echo "<input type=\"submit\" name=\"$varname\" value=\"".lang($value)."\" class=\"btn\">\n";
	} elseif($type == 'checkbox') {
		if(!is_array($varname) && !is_array($value)) {
			echo "<input type=\"checkbox\" class=\"ckb\" id=\"$varname\" name=\"$varname\" value=\"1\"".($value ? 'checked="checked"' : '')."><label for=\"$varname\">".lang($setname.'_check_label')."</label>\n";
		}
	} else {
		echo $value;
	}

	if($error) {
		$comment = '<div class="comm red">'.(is_string($error) ? lang($error) : lang($setname.'_error')).'</div>';
	} else {
		$comment = lang($setname.'_comment', false);
		if($comment) {
			$comment = '<div class="comm">'.$comment.'</div>';
		}
	}
	echo "$comment\n</div>\n";
	return true;
}

function show_step($step) {

	global $method;

	$laststep = 4;
	$title = lang('step_'.$method.'_title');
	$comment = lang('step_'.$method.'_desc');
	$step_title_1 = lang('step_title_1');
	$step_title_2 = lang('step_title_2');
	$step_title_3 = lang('step_title_3');
	$step_title_4 = lang('step_title_4');

	$stepclass = array();
	for($i = 1; $i <= $laststep; $i++) {
		$stepclass[$i] = $i == $step ? 'current' : ($i < $step ? '' : 'unactivated');
	}
	$stepclass[$laststep] .= ' last';

	echo <<<EOT
</div>
<div class="setup">
	<div>
		<div class="step step{$step}">
			<div class="stepnum">{$step}</div>
			<div>
				<h2>$title</h2>
				<p>$comment</p>
			</div>
		</div>
		<div class="stepstat">
			<div class="stepstattxt">
				<div class="$stepclass[1]">$step_title_1</div>
				<div class="$stepclass[2]">$step_title_2</div>
				<div class="$stepclass[3]">$step_title_3</div>
				<div class="$stepclass[4]">$step_title_4</div>
			</div>
			<div class="stepstatbg stepstat{$step}"></div>
		</div>
	</div>
</div>
<div class="main">
EOT;

}

function lang($lang_key, $force = true) {
	return isset($GLOBALS['lang'][$lang_key]) ? $GLOBALS['lang'][$lang_key] : ($force ? $lang_key : '');
}

function check_adminuser($username, $password, $email) {

	include ROOT_PATH.CONFIG_UC;
	include ROOT_PATH.'./uc_client/client.php';

	$error = '';
	$ucresult = uc_user_login($username, $password);
	list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email']) = uc_addslashes($ucresult);
	$ucresult = $tmp;
	if($ucresult['uid'] <= 0) {
		$uid = uc_user_register($username, $password, $email);
		if($uid == -1 || $uid == -2) {
			$error = 'admin_username_invalid';
		} elseif($uid == -4 || $uid == -5 || $uid == -6) {
			$error = 'admin_email_invalid';
		} elseif($uid == -3) {
			$error = 'admin_exist_password_error';
		}
	} else {
		$uid = $ucresult['uid'];
		$email = $ucresult['email'];
		$password = $ucresult['password'];
	}

	if(!$error && $uid > 0) {
		$password = md5($password);
		uc_user_addprotected($username, '');
	} else {
		$uid = 0;
		$error = empty($error) ? 'error_unknow_type' : $error;
	}
	return array('uid' => $uid, 'username' => $username, 'password' => $password, 'email' => $email, 'error' => $error);
}

function save_uc_config($config, $file) {

	list($appauthkey, $appid, $ucdbhost, $ucdbname, $ucdbuser, $ucdbpw, $ucdbcharset, $uctablepre, $uccharset, $ucapi, $ucip, $dzucstl) = $config;
	mysqli_report(MYSQLI_REPORT_OFF);

	$link = new mysqli($ucdbhost, $ucdbuser, $ucdbpw, $ucdbname);
	$uc_connnect = $link ? 'mysql' : '';

	$date = gmdate("Y-m-d H:i:s", time() + 3600 * 8);
	$year = date('Y');
	$config = <<<EOT
<?php


define('UC_CONNECT', '$uc_connnect');
define('UC_STANDALONE', $dzucstl);

define('UC_DBHOST', '$ucdbhost');
define('UC_DBUSER', '$ucdbuser');
define('UC_DBPW', '$ucdbpw');
define('UC_DBNAME', '$ucdbname');
define('UC_DBCHARSET', '$ucdbcharset');
define('UC_DBTABLEPRE', '`$ucdbname`.$uctablepre');
define('UC_DBCONNECT', 0);

define('UC_AVTURL', '');
define('UC_AVTPATH', '');

define('UC_CHARSET', '$uccharset');
define('UC_KEY', '$appauthkey');
define('UC_API', '$ucapi');
define('UC_APPID', '$appid');
define('UC_IP', '$ucip');
define('UC_PPP', 20);
?>
EOT;

	if(file_put_contents($file, $config) !== false) {
		return true;
	}

	return false;
}

function _generate_key($length = 32) {
	$random = secrandom($length);
	$info = md5($_SERVER['SERVER_SOFTWARE'].$_SERVER['SERVER_NAME'].(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '').(isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '').$_SERVER['HTTP_USER_AGENT'].time());
	$return = '';
	for($i=0; $i<$length; $i++) {
		$return .= $random[$i].$info[$i];
	}
	return $return;
}

function uc_write_config($config, $file, $password) {
	list($appauthkey, $appid, $ucdbhost, $ucdbname, $ucdbuser, $ucdbpw, $ucdbcharset, $uctablepre, $uccharset, $ucapi, $ucip, $dzucstl) = $config;
	$ucauthkey = _generate_key();
	$ucsiteid = _generate_key();
	$ucmykey = _generate_key();
	$salt = '';
	$pw = password_hash($password, PASSWORD_BCRYPT);
	$config = "<?php \r\ndefine('UC_DBHOST', '$ucdbhost');\r\n";
	$config .= "define('UC_DBUSER', '$ucdbuser');\r\n";
	$config .= "define('UC_DBPW', '$ucdbpw');\r\n";
	$config .= "define('UC_DBNAME', '$ucdbname');\r\n";
	$config .= "define('UC_DBCHARSET', '$ucdbcharset');\r\n";
	$config .= "define('UC_DBTABLEPRE', '$uctablepre');\r\n";
	$config .= "define('UC_COOKIEPATH', '/');\r\n";
	$config .= "define('UC_COOKIEDOMAIN', '');\r\n";
	$config .= "define('UC_DBCONNECT', 0);\r\n";
	$config .= "define('UC_CHARSET', '".$uccharset."');\r\n";
	$config .= "define('UC_FOUNDERPW', '$pw');\r\n";
	$config .= "define('UC_FOUNDERSALT', '$salt');\r\n";
	$config .= $dzucstl ? '// ' : '';
	$config .= "define('UC_KEY', '$ucauthkey');\r\n";
	$config .= "define('UC_SITEID', '$ucsiteid');\r\n";
	$config .= "define('UC_MYKEY', '$ucmykey');\r\n";
	$config .= "define('UC_DEBUG', false);\r\n";
	$config .= "define('UC_PPP', 20);\r\n";
	$config .= "define('UC_ONLYREMOTEADDR', 1);\r\n";
	$config .= "define('UC_IPGETTER', 'header');\r\n";
	$config .= "// define('UC_IPGETTER_HEADER', serialize(array('header' => 'HTTP_X_FORWARDED_FOR')));\r\n";

	file_put_contents($file, $config);
}

function install_uc_server() {
	global $db, $dbhost, $dbuser, $dbpw, $dbname, $tablepre, $username, $password, $email, $dzucstl, $myisam2innodb;

	$ucsql = file_get_contents(ROOT_PATH.'./uc_server/install/uc.sql');
	$uctablepre = $tablepre.'ucenter_';
	$ucsql = str_replace(' uc_', ' '.$uctablepre, $ucsql);
	if ($ucsql) {
		if($myisam2innodb) {
			$ucsql = str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', $ucsql);
		}
		if (!runucquery($ucsql, $uctablepre)) {
			exit();
		}
	}
	$appauthkey = _generate_key();
	$ucdbhost = $dbhost;
	$ucdbname = $dbname;
	$ucdbuser = $dbuser;
	$ucdbpw = $dbpw;
	$ucdbcharset = DBCHARSET;

	$uccharset = CHARSET;

	$pathinfo = pathinfo($_SERVER['PHP_SELF']);
	$pathinfo['dirname'] = substr($pathinfo['dirname'], 0, -8);
	$isHTTPS = is_https();
	$appurl = 'http'.($isHTTPS ? 's' : '').'://'. $_SERVER['HTTP_HOST'].$pathinfo['dirname'];
	$ucapi = $appurl.'/uc_server';
	$ucip = '';

	$db->query("INSERT INTO {$uctablepre}applications SET name='Discuz! Board', url='$appurl', ip='$ucip', authkey='$appauthkey', synlogin='1', charset='$uccharset', dbcharset='$ucdbcharset', type='DISCUZX', recvnote='1', tagtemplates=''");
	$appid = $db->insert_id();
	$db->query("ALTER TABLE {$uctablepre}notelist ADD COLUMN app$appid tinyint NOT NULL");

	$config = array($appauthkey,$appid,$ucdbhost,$ucdbname,$ucdbuser,$ucdbpw,$ucdbcharset,$uctablepre,$uccharset,$ucapi,$ucip,$dzucstl);
	save_uc_config($config, ROOT_PATH.'./config/config_ucenter.php');

	$salt = '';
	$passwordhash = password_hash($password, PASSWORD_BCRYPT);
	$db->query("INSERT INTO {$uctablepre}members SET username='$username', password='$passwordhash', email='$email', regip='hidden', regdate='".time()."', salt='$salt'");
	$uid = $db->insert_id();
	$db->query("INSERT INTO {$uctablepre}memberfields SET uid='$uid'");

	$db->query("INSERT INTO {$uctablepre}admins SET
		uid='$uid',
		username='$username',
		allowadminsetting='1',
		allowadminapp='1',
		allowadminuser='1',
		allowadminbadword='1',
		allowadmincredits='1',
		allowadmintag='1',
		allowadminpm='1',
		allowadmindomain='1',
		allowadmindb='1',
		allowadminnote='1',
		allowadmincache='1',
		allowadminlog='1'");

	uc_write_config($config, ROOT_PATH.'./uc_server/data/config.inc.php', $password);

	@unlink(ROOT_PATH.'./uc_server/install/index.php');
	@unlink(ROOT_PATH.'./uc_server/data/cache/settings.php');
	@touch(ROOT_PATH.'./uc_server/data/upgrade.lock');
	@touch(ROOT_PATH.'./uc_server/data/install.lock');
	dir_clear(ROOT_PATH.'./uc_server/data/cache');
	dir_clear(ROOT_PATH.'./uc_server/data/view');
}

function install_data($username, $uid) {
	global $_G, $db, $tablepre;
	showjsmessage(lang('install_data')." ... " . "\n");

	$_G = array('db'=>$db,'tablepre'=>$tablepre, 'uid'=>$uid, 'username'=>$username);

	$arr = array(
			0=> array('importfile'=>'./data/group_index.xml','primaltplname'=>'group/index', 'targettplname'=>'group/index'),
	);
	foreach ($arr as $v) {
		import_diy($v['importfile'], $v['primaltplname'], $v['targettplname']);
	}

	showjsmessage(lang('install_data').lang('succeed') . "\n");
}

function install_testdata($username, $uid) {
	global $_G, $db, $tablepre;

	showjsmessage(lang('install_test_data')." :  \n");
	$sqlfile = ROOT_PATH.'./install/data/common_district_{#id}.sql';
	for($i = 1; $i < 4; $i++) {
		$sqlfileid = str_replace('{#id}', $i, $sqlfile);
		if(file_exists($sqlfileid)) {
			$sql = file_get_contents($sqlfileid);
			$sql = str_replace("\r\n", "\n", $sql);
			runquery($sql);
		}
	}
}

function getvars($data, $type = 'VAR') {
	$evaluate = '';
	foreach($data as $key => $val) {
		if(!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $key)) {
			continue;
		}
		if(is_array($val)) {
			$evaluate .= buildarray($val, 0, "\${$key}")."\r\n";
		} else {
			$val = addcslashes($val, '\'\\');
			$evaluate .= $type == 'VAR' ? "\$$key = '$val';\n" : "define('".strtoupper($key)."', '$val');\n";
		}
	}
	return $evaluate;
}

function buildarray($array, $level = 0, $pre = '$_config') {
	static $ks;
	$return = '';

	if($level == 0) {
		$ks = array();
	}

	foreach ($array as $key => $val) {
		if(!preg_match("/^[a-zA-Z0-9_\x7f-\xff]+$/", $key)) {
			continue;
		}

		if($level == 0) {
			$newline = str_pad('  CONFIG '.strtoupper($key).'  ', 70, '-', STR_PAD_BOTH);
			$return .= "\r\n// $newline //\r\n";
			if($key == 'admincp') {
				$newline = str_pad(' Founders: $_config[\'admincp\'][\'founder\'] = \'1,2,3\'; ', 70, '-', STR_PAD_BOTH);
				$return .= "// $newline //\r\n";
			}
		}
		$ks[$level] = $level ? $ks[$level - 1] : '';
		if(is_int($key)) {
			$ks[$level] .= '['.$key.']';
		} else {
			$ks[$level] .= "['$key']";
		}
		if(is_array($val)) {
			$return .= buildarray($val, $level + 1, $pre);
		} else {
			$val =  is_string($val) || strlen($val) > 12 || ($val !== 0 && !preg_match("/^\-?[1-9]\d*$/", $val)) ? '\''.addcslashes($val, '\'\\').'\'' : $val;
			$return .= $pre.$ks[$level]." = $val;\r\n";
		}
	}
	return $return;
}

function save_diy_data($primaltplname, $targettplname, $data, $database = false) {
	global $_G;
	if (empty($data) || !is_array($data)) return false;

	$_G['curtplbid'] = array();
	$_G['curtplframe'] = array();

	$tpldirectory = './template/default';
	$file = '.'.$tpldirectory.'/'.$primaltplname.'.htm';
	$content = file_get_contents(realpath($file));
	foreach ($data['layoutdata'] as $key => $value) {
		$html = '';
		$html .= '<div id="'.$key.'" class="area">';
		$html .= getframehtml($value);
		$html .= '</div>';
		$content = preg_replace("/(\<\!\-\-\[diy\=$key\]\-\-\>).+?(\<\!\-\-\[\/diy\]\-\-\>)/is", "\\1".$html."\\2", $content);
	}
	$content = preg_replace("/(\<style id\=\"diy_style\" type\=\"text\/css\"\>).*(\<\/style\>)/is", "\\1".$data['spacecss']."\\2", $content);
	if (!empty($data['style'])) {
		$content = preg_replace("/(\<link id\=\"style_css\" rel\=\"stylesheet\" type\=\"text\/css\" href\=\").+?(\"\>)/is", "\\1".$data['style']."\\2", $content);
	}

	$tplfile =ROOT_PATH.'./data/diy/'.$tpldirectory.'/'.$targettplname.'.htm';

	$tplpath = dirname($tplfile);
	if (!is_dir($tplpath)) dmkdir($tplpath);
	$r = file_put_contents($tplfile, $content);

	if ($r && $database) {
		$_G['db']->query('DELETE FROM '.$_G['tablepre'].'common_template_block WHERE targettplname="'.$targettplname.'"');
		if (!empty($_G['curtplbid'])) {
			$values = array();
			foreach ($_G['curtplbid'] as $bid) {
				$values[] = "('$targettplname', '$tpldirectory', '$bid')";
			}
			if (!empty($values)) {
				$_G['db']->query("INSERT INTO ".$_G['tablepre']."common_template_block (targettplname, tpldirectory, bid) VALUES ".implode(',', $values));
			}
		}

		$tpldata = daddslashes(serialize($data));
		$_G['db']->query("REPLACE INTO ".$_G['tablepre']."common_diy_data (targettplname, tpldirectory, primaltplname, diycontent) VALUES ('$targettplname', '$tpldirectory', '$primaltplname', '$tpldata')");
	}

	return $r;
}

function getframehtml($data = array()) {
	global $_G;
	$html = $style = '';
	foreach ((array)$data as $id => $content) {
		list($flag, $name) = explode('`', $id.'`');
		if ($flag == 'frame') {
			$fattr = $content['attr'];
			$moveable = $fattr['moveable'] == 'true' ? ' move-span' : '';
			$html .= '<div id="'.$fattr['name'].'" class="'.$fattr['className'].'">';
			if (checkhastitle($fattr['titles'])) {
				$style = gettitlestyle($fattr['titles']);
				$html .= '<div class="'.implode(' ',$fattr['titles']['className']).'"'.$style.'>'.gettitlehtml($fattr['titles'], 'frame').'</div>';
			}
			foreach ((array)$content as $colid => $coldata) {
				list($colflag, $colname) = explode('`', $colid.'`');
				if ($colflag == 'column') {
					$html .= '<div id="'.$colname.'" class="'.$coldata['attr']['className'].'">';
					$html .= '<div id="'.$colname.'_temp" class="move-span temp"></div>';
					$html .= getframehtml($coldata);
					$html .= '</div>';
				}
			}
			$html .= '</div>';
		} elseif ($flag == 'tab') {
			$fattr = $content['attr'];
			$moveable = $fattr['moveable'] == 'true' ? ' move-span' : '';
			$html .= '<div id="'.$fattr['name'].'" class="'.$fattr['className'].'">';
			$switchtype = 'click';
			foreach ((array)$content as $colid => $coldata) {
				list($colflag, $colname) = explode('`', $colid);
				if ($colflag == 'column') {
					if (checkhastitle($fattr['titles'])) {
						$style = gettitlestyle($fattr['titles']);
						$title = gettitlehtml($fattr['titles'], 'tab');
					}
					$switchtype = is_array($fattr['titles']['switchType']) && !empty($fattr['titles']['switchType'][0]) ? $fattr['titles']['switchType'][0] : 'click';
					$html .= '<div id="'.$colname.'" class="'.$coldata['attr']['className'].'"'.$style.' switchtype="'.$switchtype.'">'.$title;
					$html .= getframehtml($coldata);
					$html .= '</div>';
				}
			}
			$html .= '<div id="'.$fattr['name'].'_content" class="tb-c"></div>';
			$html .= '<script type="text/javascript">initTab("'.$fattr['name'].'","'.$switchtype.'");</script>';
			$html .= '</div>';
		} elseif ($flag == 'block') {
			$battr = $content['attr'];
			$bid = intval(str_replace('portal_block_', '', $battr['name']));
			if (!empty($bid)) {
				$html .= "<!--{block/{$bid}}-->";
				$_G['curtplbid'][$bid] = $bid;
			}
		}
	}

	return $html;
}
function gettitlestyle($title) {
	$style = '';
	if (is_array($title['style']) && count($title['style'])) {
		foreach ($title['style'] as $k=>$v){
			$style .= $k.':'.$v.';';
		}
	}
	$style = $style ? ' style=\''.$style.'\'' : '';
	return $style;
}
function checkhastitle($title) {
	if (!is_array($title)) return false;
	foreach ($title as $k => $v) {
		if (strval($k) == 'className') continue;
		if (!empty($v['text'])) return true;
	}
	return false;
}

function gettitlehtml($title, $type) {
	global $_G;
	if (!is_array($title)) return '';
	$html = $one = $style = $color =  '';
	foreach ($title as $k => $v) {
		if (in_array(strval($k),array('className','style'))) continue;
		if (empty($v['src']) && empty($v['text'])) continue;
		$one = "<span class=\"{$v['className']}\"";
		$style = $color = "";
		$style .= empty($v['font-size']) ? '' : "font-size:{$v['font-size']}px;";
		$style .= empty($v['float']) ? '' : "float:{$v['float']};";
		$margin_ = empty($v['float']) ? 'left' : $v['float'];
		$style .= empty($v['margin']) ? '' : "margin-{$margin_}:{$v['margin']}px;";
		$color = empty($v['color']) ? '' : "color:{$v['color']};";
		$img = !empty($v['src']) ? '<img src="'.$v['src'].'" class="vm" alt="'.$v['text'].'"/>' : '';
		if (empty($v['href'])) {
			$style = empty($style)&&empty($color) ? '' : ' style="'.$style.$color.'"';
			$one .= $style.">$img{$v['text']}";
		} else {
			$style = empty($style) ? '' : ' style="'.$style.'"';
			$colorstyle = empty($color) ? '' : ' style="'.$color.'"';
			$one .= $style.'><a href="'.$v['href'].'"'.$colorstyle.'>'.$img.$v['text'].'</a>';
		}
		$one .= '</span>';

		$siteurl = str_replace(array('/','.'),array('\/','\.'),$_G['siteurl']);
		$one = preg_replace('/\"'.$siteurl.'(.*?)\"/','"$1"',$one);

		$html = $k === 'first' ? $one.$html : $html.$one;
	}
	return $html;
}

function block_import($data) {
	global $_G;
	if(!is_array($data['block'])) {
		return ;
	}
	$data = daddslashes($data);
	$stylemapping = array();
	if($data['style']) {
		$hashes = $styles = array();
		foreach($data['style'] as $value) {
			$hashes[] = $value['hash'];
			$styles[$value['hash']] = $value['styleid'];
		}
		$query = $_G['db']->query('SELECT styleid, hash FROM '.$_G['tablepre']."common_block_style WHERE hash IN (".dimplode($hashes).')');
		while($value=$_G['db']->fetch_array($query)) {
			$id = $styles[$value['hash']];
			$stylemapping[$id] = intval($value['styleid']);
			unset($styles[$value['hash']]);
		}
		foreach($styles as $id) {
			$style = $data['style'][$id];
			$style['styleid'] = '';
			if(is_array($style['template'])) {
				$style['template'] = dstripslashes($style['template']);
				$style['template'] = addslashes(serialize($style['template']));
			}
			$sql = implode_field_value($style);
			$_G['db']->query('INSERT INTO '.$_G['tablepre'].'common_block_style SET '.$sql);
			$newid = $_G['db']->insert_id();
			$stylemapping[$id] = $newid;
		}
	}

	$blockmapping = array();
	foreach($data['block'] as $block) {
		$oid = $block['bid'];
		if(!empty($block['styleid'])) {
			$block['styleid'] = intval($stylemapping[$block['styleid']]);
		}
		$block['bid'] = '';
		$block['uid'] = $_G['uid'];
		$block['username'] = $_G['username'];
		$block['dateline'] = 0;
		if(is_array($block['param'])) {
			$block['param'] = dstripslashes($block['param']);
			$block['param'] = addslashes(serialize($block['param']));
		}
		$sql = implode_field_value($block);
		$_G['db']->query('INSERT INTO '.$_G['tablepre'].'common_block SET '.$sql);
		$newid = $_G['db']->insert_id();
		$blockmapping[$oid] = $newid;
	}
	return $blockmapping;
}

function getframeblock($data) {
	global $_G;

	if (!isset($_G['curtplbid'])) $_G['curtplbid'] = array();
	if (!isset($_G['curtplframe'])) $_G['curtplframe'] = array();

	foreach ((array)$data as $id => $content) {
		list($flag, $name) = explode('`', $id.'`');
		if ($flag == 'frame' || $flag == 'tab') {
			foreach ((array)$content as $colid => $coldata) {
				list($colflag, $colname) = explode('`', $colid.'`');
				if ($colflag == 'column') {
					getframeblock($coldata);
				}
			}
			$_G['curtplframe'][$name] = array('type'=>$flag,'name'=>$name);
		} elseif ($flag == 'block') {
			$battr = $content['attr'];
			$bid = intval(str_replace('portal_block_', '', $battr['name']));
			if (!empty($bid)) {
				$_G['curtplbid'][$bid] = $bid;
			}
		}
	}
}

function import_diy($importfile, $primaltplname, $targettplname) {
	global $_G;

	$css = $html = '';
	$arr = array();

	$content = file_get_contents(realpath($importfile));
	require_once ROOT_PATH.'./source/class/class_xml.php';
	if (empty($content)) return $arr;
	$diycontent = xml2array($content);
	$diycontent = is_array($diycontent) ? $diycontent : array();

	if ($diycontent) {

		foreach ($diycontent['layoutdata'] as $key => $value) {
			if (!empty($value)) getframeblock($value);
		}
		$newframe = array();
		foreach ($_G['curtplframe'] as $value) {
			$newframe[] = $value['type'].random(6);
		}

		$mapping = array();
		if (!empty($diycontent['blockdata'])) {
			$mapping = block_import($diycontent['blockdata']);
			unset($diycontent['blockdata']);
		}

		$oldbids = $newbids = array();
		if (!empty($mapping)) {
			foreach($mapping as $obid=>$nbid) {
				$oldbids[] = 'portal_block_'.$obid;
				$newbids[] = 'portal_block_'.$nbid;
			}
		}

		require_once ROOT_PATH.'./source/class/class_xml.php';
		$xml = array2xml($diycontent['layoutdata'],true);
		$xml = str_replace($oldbids, $newbids, $xml);
		$xml = str_replace((array)array_keys($_G['curtplframe']), $newframe, $xml);
		$diycontent['layoutdata'] = xml2array($xml);

		$css = str_replace($oldbids, $newbids, $diycontent['spacecss']);
		$css = str_replace((array)array_keys($_G['curtplframe']), $newframe, $css);

		$arr['spacecss'] = $css;
		$arr['layoutdata'] = $diycontent['layoutdata'];
		$arr['style'] = $diycontent['style'];
		save_diy_data($primaltplname, $targettplname, $arr, true);
	}
	return $arr;
}
function dimplode($array) {
	if(!empty($array)) {
		return "'".implode("','", is_array($array) ? $array : array($array))."'";
	} else {
		return '';
	}
}
function implode_field_value($array, $glue = ',') {
	$sql = $comma = '';
	foreach ($array as $k => $v) {
		$sql .= $comma."`$k`='".(is_string($v) ? $v : '')."'";
		$comma = $glue;
	}
	return $sql;
}

function daddslashes($string, $force = 1) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = daddslashes($val, $force);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}
function dstripslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}
function dmkdir($dir, $mode = 0777){
	if(!is_dir($dir)) {
		dmkdir(dirname($dir), $mode);
		@mkdir($dir, $mode);
		@touch($dir.'/index.htm'); @chmod($dir.'/index.htm', 0777);
	}
	return true;
}
function dhtmlspecialchars($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val);
		}
	} else {
		$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
		if(strpos($string, '&amp;#') !== false) {
			$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
		}
	}
	return $string;
}
function install_extra_setting() {
	global $db, $tablepre, $lang;
	include ROOT_PATH.'./install/include/install_extvar.php';
	foreach($settings as $key => $val) {
		$db->query("REPLACE INTO {$tablepre}common_setting SET skey='$key', svalue='".addslashes(serialize($val))."'");
	}
}
function format_space($space) {
    if($space > 1048576) {
		if($space > 1073741824) {
			return floor($space / 1073741824).'GB';
		} else {
			return floor($space / 1048576).'MB';
		}
	}
	return $space;
}

function init_install_log_file() {
	if (file_exists(INST_LOG_PATH)) {
		append_to_install_log_file("", true);
		unlink(INST_LOG_PATH);
	}
}

function append_to_install_log_file($message, $close = false) {
	static $fh = false;
	if (!$fh) {
		$fh = fopen(INST_LOG_PATH, "a+");
	}
	if ($fh) {
		fwrite($fh, $message);
		fflush($fh);
		if ($close) {
			fclose($fh);
		}
	}
}

function read_install_log_file() {
	if (file_exists(INST_LOG_PATH)) {
		$offset = intval(getgpc('offset'));
		echo sprintf('%05d',filesize(INST_LOG_PATH));
		if($offset) {
			$fp = fopen(INST_LOG_PATH, 'rb');
			fseek($fp, $offset);
			fpassthru($fp);
		} else {
			readfile(INST_LOG_PATH);
		}
	} else {
		echo '00000';
	}
}

function send_mime_type_header($type = 'application/xml') {
	header("Content-Type: ".$type);
}

function is_https() {
	// PHP 标准服务器变量
	if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
		return true;
	}
	// X-Forwarded-Proto 事实标准头部, 用于反代透传 HTTPS 状态
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
		return true;
	}
	// 阿里云全站加速私有 HTTPS 状态头部
	// Git 意见反馈 https://gitee.com/Discuz/DiscuzX/issues/I3W5GP
	if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
		return true;
	}
	// 西部数码建站助手私有 HTTPS 状态头部
	// 官网意见反馈 https://discuz.dismall.com/thread-3849819-1-1.html
	if(isset($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) != 'off') {
		return true;
	}
	// 服务器端口号兜底判断
	if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
		return true;
	}
	return false;
}

function getmaxupload() {
	$sizeconv = array('B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824);
	$sizes = array();
	$sizes[] = ini_get('upload_max_filesize');
	$sizes[] = ini_get('post_max_size');
	$sizes[] = ini_get('memory_limit');
	if(intval($sizes[1]) === 0) {
		unset($sizes[1]);
	}
	if(intval($sizes[2]) === -1) {
		unset($sizes[2]);
	}
	$sizes = preg_replace_callback(
		'/^(\-?\d+)([KMG]?)$/i',
		function($arg) use ($sizeconv) {
			return (intval($arg[1]) * $sizeconv[strtoupper($arg[2]).'B']).'|'.strtoupper($arg[0]);
		},
		$sizes
	);
	natsort($sizes);
	$output = explode('|', current($sizes));
	if(!empty($output[1])) {
		return $output[1];
	} else {
		return ini_get('upload_max_filesize');
	}
}
