<?php

/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forum.func.php 14122 2008-08-20 06:06:33Z cnteacher $
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
			echo "<div class=\"desc\"><b>$title</b><ul>$comment</ul>";
		} else {
			echo "</div><div class=\"main\" style=\"margin-top: -123px;\"><b>$title</b><ul style=\"line-height: 200%; margin-left: 30px;\">$comment</ul>";
		}

		if($quit) {
			echo '<br /><span class="red">'.lang('error_quit_msg').'</span><br /><br /><br />';
		}

		echo '<input type="button" onclick="history.back()" value="'.lang('click_to_back').'" /><br /><br /><br />';

		echo '</div>';

		$quit && show_footer();
	}
}

function check_db($dbhost, $dbuser, $dbpw, $dbname, $tablepre) {
	if(!function_exists('mysqli_connect')) {
		show_msg('undefine_func', 'mysqli_connect', 0);
	}
	$link = new mysqli($dbhost, $dbuser, $dbpw);
	if(!$link) {
		$errno = mysqli_errno($link);
		$error = mysqli_error($link);
		if($errno == 1045) {
			show_msg('database_errno_1045', $error, 0);
		} elseif($errno == 2003) {
			show_msg('database_errno_2003', $error, 0);
		} else {
			show_msg('database_connect_error', $error, 0);
		}
	} else {
		if($query = ($link->query("SHOW TABLES FROM $dbname"))) {
			if(!$query) {
				return false;
			}
			while($row = ($query->fetch_row())) {
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
			$env_items[$key]['current'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
		} elseif($key == 'gdversion') {
			$tmp = function_exists('gd_info') ? gd_info() : array();
			$env_items[$key]['current'] = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
			unset($tmp);
		} elseif($key == 'diskspace') {
			if(function_exists('disk_free_space')) {
				$env_items[$key]['current'] = floor(disk_free_space(ROOT_PATH) / (1024*1024)).'M';
			} else {
				$env_items[$key]['current'] = 'unknow';
			}
		} elseif(isset($item['c'])) {
			$env_items[$key]['current'] = constant($item['c']);
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

function show_env_result(&$env_items, &$dirfile_items, &$func_items) {

	$env_str = $file_str = $dir_str = $func_str = '';
	$error_code = 0;

	foreach($env_items as $key => $item) {
		if($key == 'php' && strcmp($item['current'], $item['r']) < 0) {
			show_msg('php_version_too_low', $item['current'], 0);
		}
		$status = 1;
		if($item['r'] != 'notset') {
			if(intval($item['current']) && intval($item['r'])) {
				if(intval($item['current']) < intval($item['r'])) {
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
		if(VIEW_OFF) {
			$env_str .= "\t\t<runCondition name=\"$key\" status=\"$status\" Require=\"{$item['r']}\" Best=\"{$item['b']}\" Current=\"{$item['current']}\"/>\n";
		} else {
			$env_str .= "<tr>\n";
			$env_str .= "<td>".lang($key)."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['r'])."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['b'])."</td>\n";
			$env_str .= ($status ? "<td class=\"w pdleft1\">" : "<td class=\"nw pdleft1\">").$item['current']."</td>\n";
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
			$$variable .= "<tr>\n";
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

		echo "<h2 class=\"title\">".lang('env_check')."</h2>\n";
		echo "<table class=\"tb\" style=\"margin:20px 0 20px 55px;\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('project')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_required')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_best')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('curr_server')."</th>\n";
		echo "</tr>\n";
		echo $env_str;
		echo "</table>\n";

		echo "<h2 class=\"title\">".lang('priv_check')."</h2>\n";
		echo "<table class=\"tb\" style=\"margin:20px 0 20px 55px;width:90%;\">\n";
		echo "\t<tr>\n";
		echo "\t<th>".lang('step1_file')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_need_status')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_status')."</th>\n";
		echo "</tr>\n";
		echo $file_str;
		echo $dir_str;
		echo "</table>\n";

		foreach($func_items as $item) {
			$status = function_exists($item);
			$func_str .= "<tr>\n";
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
		echo "<h2 class=\"title\">".lang('func_depend')."</h2>\n";
		echo "<table class=\"tb\" style=\"margin:20px 0 20px 55px;width:90%;\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('func_name')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('check_result')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('suggestion')."</th>\n";
		echo "</tr>\n";
		echo $func_str;
		echo "</table>\n";

		show_next_step(2, $error_code);

		show_footer();

	}

}

function show_next_step($step, $error_code) {
	echo "<form action=\"index.php\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"step\" value=\"$step\" />";
	if(isset($GLOBALS['hidden'])) {
		echo $GLOBALS['hidden'];
	}
	if($error_code == 0) {
		$nextstep = "<input type=\"button\" onclick=\"history.back();\" value=\"".lang('old_step')."\"><input type=\"submit\" value=\"".lang('new_step')."\">\n";
	} else {
		$nextstep = "<input type=\"button\" disabled=\"disabled\" value=\"".lang('not_continue')."\">\n";
	}
	echo "<div class=\"btnbox marginbot\">".$nextstep."</div>\n";
	echo "</form>\n";
}

function show_form(&$form_items, $error_msg) {

	global $step;

	if(empty($form_items) || !is_array($form_items)) {
		return;
	}

	show_header();
	show_setting('start');
	show_setting('hidden', 'step', $step);
	$is_first = 1;
	foreach($form_items as $key => $items) {
		global ${'error_'.$key};
		if($is_first == 0) {
			echo '</table>';
		}

		if(!${'error_'.$key}) {
			show_tips('tips_'.$key);
		} else {
			show_error('tips_admin_config', ${'error_'.$key});
		}

		if($is_first == 0) {
			echo '<table class="tb2">';
		}

		foreach($items as $k => $v) {
			global $$k;
			if(!empty($error_msg)) {
				$value = isset($_POST[$key][$k]) ? $_POST[$key][$k] : '';
			} else {
				if(isset($v['value']) && is_array($v['value'])) {
					if($v['value']['type'] == 'constant') {
						$value = defined($v['value']['var']) ? constant($v['value']['var']) : '';
					} elseif($v['value']['type'] == 'var') {
						$value = $GLOBALS[$v['value']['var']];
					} elseif($v['value']['type'] == 'string') {
						$value = $v['value']['var'];
					}
				} else {
					$value = '';
				}
			}
			if($v['type'] == 'checkbox') {
				$value = '1';
			}
			show_setting($k, $key.'['.$k.']', $value, $v['type'], isset($error_msg[$key][$k]) ? $key.'_'.$k.'_invalid' : '');
		}

		if($is_first) {
			$is_first = 0;
		}
	}
	show_setting('', 'submitname', 'new_step', 'submit');
	show_setting('end');
	show_footer();
}

function show_license() {
	global $self, $uchidden, $step;
	$next = $step + 1;
	if(VIEW_OFF) {

		show_msg('license_contents', lang('license'), 1);

	} else {

		show_header();

		$license = str_replace('  ', '&nbsp; ', lang('license'));
		$lang_agreement_yes = lang('agreement_yes');
		$lang_agreement_no = lang('agreement_no');
		echo <<<EOT
</div>
<div class="main" style="margin-top:-123px;">
	<div class="licenseblock">$license</div>
	<div class="btnbox marginbot">
		<form method="get" action="index.php">
		<input type="hidden" name="step" value="$next">
		$uchidden
		<input type="submit" name="submit" value="{$lang_agreement_yes}" style="padding: 2px">&nbsp;
		<input type="button" name="exit" value="{$lang_agreement_no}" style="padding: 2px" onclick="javascript: window.close(); return false;">
		</form>
	</div>
EOT;

		show_footer();

	}
}

if(!function_exists('file_put_contents')) {
	function file_put_contents($filename, $s) {
		$fp = @fopen($filename, 'w');
		@fwrite($fp, $s);
		@fclose($fp);
		return TRUE;
	}
}

function createtable($sql) {
	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
	$type = in_array($type, array('INNODB', 'MYISAM', 'HEAP', 'MEMORY')) ? $type : 'INNODB';
	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).
	(" ENGINE=$type DEFAULT CHARSET=".DBCHARSET);// 不考虑低版本MySQL, Git新增
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
	showjsmessage($lang['clear_dir'].' '.str_replace(ROOT_PATH, '', $dir));
	$directory = dir($dir);
	while($entry = $directory->read()) {
		$filename = $dir.'/'.$entry;
		if(is_file($filename)) {
			@unlink($filename);
		}
	}
	$directory->close();
	@touch($dir.'/index.htm');
}

function show_header() {
	define('SHOW_HEADER', TRUE);
	global $step;
	$version = SOFT_VERSION;
	$release = SOFT_RELEASE;
	$install_lang = lang(INSTALL_LANG);
	$title = lang('title_install');
	$charset = CHARSET;
	echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="$charset" />
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>$title</title>
<link rel="stylesheet" href="style.css" type="text/css" media="all" />
<script type="text/javascript">
	function $(id) {
		return document.getElementById(id);
	}

	function showmessage(message) {
		$('notice').value += message + "\\r\\n";
	}
</script>
<meta content="Comsenz Inc." name="Copyright" />
</head>
<div class="container">
	<div class="header">
		<h1>$title</h1>
		<span>V$version $install_lang $release</span>
EOT;

	$step > 0 && show_step($step);
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

function loginit($logfile) {
	global $lang;
	showjsmessage($lang['init_log'].' '.$logfile);
	if($fp = @fopen('./forumdata/logs/'.$logfile.'.php', 'w')) {
		fwrite($fp, '<'.'?PHP exit(); ?'.">\n");
		fclose($fp);
	}
}

function showjsmessage($message) {
	if(VIEW_OFF) return;
	echo '<script type="text/javascript">showmessage(\''.addslashes($message).' \');</script>'."\r\n";
	flush();
	ob_flush();
}

function random($length) {
	$hash = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
	$max = strlen($chars) - 1;
	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	for($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
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

function config_edit() {
	extract($GLOBALS, EXTR_SKIP);
	$ucsalt = '';
	$ucfounderpw = password_hash($ucfounderpw, PASSWORD_BCRYPT);
	$regdate = time();

	$ucauthkey = generate_key();
	$ucsiteid = generate_key();
	$ucmykey = generate_key();
	$config = "<?php \r\ndefine('UC_DBHOST', '$dbhost');\r\n";
	$config .= "define('UC_DBUSER', '$dbuser');\r\n";
	$config .= "define('UC_DBPW', '$dbpw');\r\n";
	$config .= "define('UC_DBNAME', '$dbname');\r\n";
	$config .= "define('UC_DBCHARSET', '".DBCHARSET."');\r\n";
	$config .= "define('UC_DBTABLEPRE', '$tablepre');\r\n";
	$config .= "define('UC_COOKIEPATH', '/');\r\n";
	$config .= "define('UC_COOKIEDOMAIN', '');\r\n";
	$config .= "define('UC_DBCONNECT', 0);\r\n";
	$config .= "define('UC_CHARSET', '".CHARSET."');\r\n";
	$config .= "define('UC_FOUNDERPW', '$ucfounderpw');\r\n";
	$config .= "define('UC_FOUNDERSALT', '$ucsalt');\r\n";
	$config .= "define('UC_KEY', '$ucauthkey');\r\n";
	$config .= "define('UC_SITEID', '$ucsiteid');\r\n";
	$config .= "define('UC_MYKEY', '$ucmykey');\r\n";
	$config .= "define('UC_DEBUG', false);\r\n";
	$config .= "define('UC_PPP', 20);\r\n";
	$fp = fopen(CONFIG, 'w');
	fwrite($fp, $config);
	fclose($fp);
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
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密钥保存在密文里, 并用 base64 编码保证传输时不被破坏
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function generate_key() {
	$random = random(32);
	$info = md5($_SERVER['SERVER_SOFTWARE'].$_SERVER['SERVER_NAME'].$_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT'].$_SERVER['HTTP_USER_AGENT'].time());
	$return = array();// 按Discuz!安装文件进行修改, Git新增
	for($i=0; $i<64; $i++) {
		$p = intval($i/2);
		$return[$i] = $i % 2 ? $random[$p] : $info[$p];
	}
	return implode('', $return);
}

function show_install() {
	if(VIEW_OFF) return;
?>
<script type="text/javascript">
function showmessage(message) {
	document.getElementById('notice').value += message + "\r\n";
}
function initinput() {
	window.location='<?php echo 'index.php?step='.($GLOBALS['step']);?>';
}
</script>
	<div class="main">
		<div class="btnbox"><textarea name="notice" style="width: 80%;"  readonly="readonly" id="notice"></textarea></div>
		<div class="btnbox marginbot">
	<input type="button" name="submit" value="<?php echo lang('install_in_processed');?>" disabled style="height: 25" id="laststep" onclick="initinput()">
	</div>
<?php
}

function runquery($sql) {
	global $lang, $tablepre, $db;

	if(!isset($sql) || empty($sql)) return;

	$sql = str_replace("\r", "\n", str_replace(' '.ORIG_TABLEPRE, ' '.$tablepre, $sql));
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
				showjsmessage(lang('create_table').' '.$name.' ... '.lang('succeed'));
				$db->query(createtable($query));
			} else {
				$db->query($query);
			}

		}
	}

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
	return isset($var[$k]) ? $var[$k] : '';
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

	if(!file_exists(DISCUZ_ROOT.'./config.inc.php')) {
		$errors[] = 'config_nonexistence';
		$quit = true;
	} elseif(!is_writeable(DISCUZ_ROOT.'./config.inc.php')) {
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
		if(!dir_writeable(DISCUZ_ROOT.$dir)) {
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
		echo "</div><div class=\"main\" style=\"margin-top: -123px;\"><b>$title</b><ul style=\"line-height: 200%; margin-left: 30px;\">$comment</ul>";
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
		echo "<div class=\"desc\"><b>$title</b>";
	} else {
		echo "</div><div class=\"main\" style=\"margin-top: -123px;\">$title<div class=\"desc1 marginbot\"><ul>";
	}
	$comment && print('<br>'.$comment);
	echo "</div>";
}

function show_setting($setname, $varname = '', $value = '', $type = 'text|password|checkbox', $error = '') {
	if($setname == 'start') {
		echo "<form method=\"post\" action=\"index.php\">\n<table class=\"tb2\">\n";
		return;
	} elseif($setname == 'end') {
		echo "\n</table>\n</form>\n";
		return;
	} elseif($setname == 'hidden') {
		echo "<input type=\"hidden\" name=\"$varname\" value=\"$value\">\n";
		return;
	}

	echo "\n".'<tr><th class="tbopt'.($error ? ' red' : '').'">&nbsp;'.(empty($setname) ? '' : lang($setname).':')."</th>\n<td>";
	if($type == 'text' || $type == 'password') {
		$value = dhtmlspecialchars($value);
		echo "<input type=\"$type\" name=\"$varname\" value=\"$value\" size=\"35\" class=\"txt\">";
	} elseif($type == 'submit') {
		$value = empty($value) ? 'next_step' : $value;
		echo "<input type=\"submit\" name=\"$varname\" value=\"".lang($value)."\" class=\"btn\">\n";
	} elseif($type == 'checkbox') {
		if(!is_array($varname) && !is_array($value)) {
			echo'<label><input type="checkbox" name="'.$varname.'" value="'.$value."\" style=\"border: 0\">".lang($setname.'_check_label')."</label>\n";
		}
	} else {
		echo $value;
	}

	echo "</td>\n<td>&nbsp;";
	if($error) {
		$comment = '<span class="red">'.(is_string($error) ? lang($error) : lang($setname.'_error')).'</span>';
	} else {
		$comment = lang($setname.'_comment', false);
	}
	echo "$comment</td>\n</tr>\n";
	return true;
}

function show_step($step) {

	global $method;

	$laststep = 4;
	$title = lang('step_'.$method.'_title');
	$comment = lang('step_'.$method.'_desc');

	$stepclass = array();
	for($i = 1; $i <= $laststep; $i++) {
		$stepclass[$i] = $i == $step ? 'current' : ($i < $step ? '' : 'unactivated');
	}
	$stepclass[$laststep] .= ' last';

	echo <<<EOT
	<div class="setup step{$step}">
		<h2>$title</h2>
		<p>$comment</p>
	</div>
	<div class="stepstat">
		<ul>
			<li class="$stepclass[1]">1</li>
			<li class="$stepclass[2]">2</li>
			<li class="$stepclass[3]">3</li>
			<li class="$stepclass[4]">4</li>
		</ul>
		<div class="stepstatbg stepstat1"></div>
	</div>
</div>
<div class="main">
EOT;

}

function lang($lang_key, $force = true) {
	return isset($GLOBALS['lang'][$lang_key]) ? $GLOBALS['lang'][$lang_key] : ($force ? $lang_key : '');
}

function check_adminuser($username, $password, $email) {

	@include ROOT_PATH.'./config.inc.php';
	include ROOT_PATH.'./uc_client/client.php';
	$error = '';
	$uid = uc_user_register($username, $password, $email);
	if($uid == -1 || $uid == -2) {
		$error = 'admin_username_invalid';
	} elseif($uid == -4 || $uid == -5 || $uid == -6) {
		$error = 'admin_email_invalid';
	} elseif($uid == -3) {
		$ucresult = uc_user_login($username, $password);
		list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email']) = uc_addslashes($ucresult);
		$ucresult = $tmp;
		if($ucresult['uid'] <= 0) {
			$error = 'admin_exist_password_error';
		} else {
			$uid = $ucresult['uid'];
			$email = $ucresult['email'];
			$password = $ucresult['password'];
		}
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

function dhtmlspecialchars($string, $flags = null) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val, $flags);
		}
	} else {
		if($flags === null) {
			$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
			if(strpos($string, '&amp;#') !== false) {
				$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
			}
		} else {
			if(PHP_VERSION < '5.4.0') {
				$string = htmlspecialchars($string, $flags);
			} else {
				if(strtolower(CHARSET) == 'utf-8') {
					$charset = 'UTF-8';
				} else {
					$charset = 'ISO-8859-1';
				}
				$string = htmlspecialchars($string, $flags, $charset);
			}
		}
	}
	return $string;
}

function send_mime_type_header($type = 'application/xml') {
	header("Content-Type: ".$type);
}