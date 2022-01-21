<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: app.php 1102 2011-05-30 09:40:42Z svn_project_zhangjie $
*/

!defined('IN_UC') && exit('Access Denied');

class appcontrol extends base {

	function __construct() {
		$this->appcontrol();
	}

	function appcontrol() {
		parent::__construct();
		$this->load('app');
		$this->load('user');
	}

	function onls() {
		$this->init_input();
		$applist = $_ENV['app']->get_apps('appid, type, name, url, tagtemplates, viewprourl, synlogin');
		$applist2 = array();
		foreach($applist as $key => $app) {
			$app['tagtemplates'] = $this->unserialize($app['tagtemplates']);
			$applist2[$app['appid']] = $app;
		}
		return $applist2;
	}

	function onadd() {
		$ucfounderpw = getgpc('ucfounderpw', 'P');
		$apptype = getgpc('apptype', 'P');
		$appname = getgpc('appname', 'P');
		$appurl = getgpc('appurl', 'P');
		$appip = getgpc('appip', 'P');
		$apifilename = trim(getgpc('apifilename', 'P'));
		$viewprourl = getgpc('viewprourl', 'P');
		$appcharset = getgpc('appcharset', 'P');
		$appdbcharset = getgpc('appdbcharset', 'P');
		$apptagtemplates = getgpc('apptagtemplates', 'P');
		$appallowips = getgpc('allowips', 'P');

		$apifilename = $apifilename ? $apifilename : 'uc.php';

		if(!$this->settings['addappbyurl'] || !$_ENV['user']->can_do_login('UCenterAdministrator', $this->onlineip)) {
			exit('-1');
		}

		if($_ENV['user']->verify_password($ucfounderpw, UC_FOUNDERPW, UC_FOUNDERSALT) || (strlen($ucfounderpw) == 32 && hash_equals($ucfounderpw, md5(UC_FOUNDERPW)))) {
			@ob_start();
			$return  = '';

			$this->_writelog('login', 'succeed_by_url_add_app');

			$app = $this->db->fetch_first("SELECT * FROM ".UC_DBTABLEPRE."applications WHERE url='$appurl' AND type='$apptype'");

			if(empty($app)) {
				$authkey = $this->_generate_key();
				$apptagtemplates = $this->serialize($apptagtemplates, 1);
				$this->db->query("INSERT INTO ".UC_DBTABLEPRE."applications SET
					name='$appname',
					url='$appurl',
					ip='$appip',
					apifilename='$apifilename',
					authkey='$authkey',
					viewprourl='$viewprourl',
					synlogin='1',
					charset='$appcharset',
					dbcharset='$appdbcharset',
					type='$apptype',
					recvnote='1',
					tagtemplates='$apptagtemplates',
					allowips='$appallowips'
					");
				$appid = $this->db->insert_id();

				$this->_writelog('app_add', "appid=$appid; appname=$appname; by=url_add");

				$_ENV['app']->alter_app_table($appid, 'ADD');
				$return = "$authkey|$appid|".UC_DBHOST.'|'.UC_DBNAME.'|'.UC_DBUSER.'|'.UC_DBPW.'|'.UC_DBCHARSET.'|'.UC_DBTABLEPRE.'|'.UC_CHARSET;
				$this->load('cache');
				$_ENV['cache']->updatedata('apps');

				$this->load('note');
				$notedata = $this->db->fetch_all("SELECT appid, type, name, url, ip, charset, synlogin, extra FROM ".UC_DBTABLEPRE."applications");
				$notedata = $this->_format_notedata($notedata);
				$notedata['UC_API'] = UC_API;
				$_ENV['note']->add('updateapps', '', $this->serialize($notedata, 1));
				$_ENV['note']->send();
			} else {
				$this->_writelog('app_queryinfo', "appid={$app['appid']}; by=url_add");
				$return = "{$app['authkey']}|{$app['appid']}|".UC_DBHOST.'|'.UC_DBNAME.'|'.UC_DBUSER.'|'.UC_DBPW.'|'.UC_DBCHARSET.'|'.UC_DBTABLEPRE.'|'.UC_CHARSET;
			}
			@ob_end_clean();
			exit($return);
		} else {
			$pwlen = strlen($ucfounderpw);
			$this->_writelog('login', 'error_by_url_add_app: user=UCenterAdministrator; password='.($pwlen > 2 ? preg_replace("/^(.{".round($pwlen / 4)."})(.+?)(.{".round($pwlen / 6)."})$/s", "\\1***\\3", $ucfounderpw) : $ucfounderpw));

			$_ENV['user']->loginfailed('UCenterAdministrator', $this->onlineip);

			exit('-1');
		}
	}

	function onucinfo() {
		$arrapptypes = $this->db->fetch_all("SELECT DISTINCT type FROM ".UC_DBTABLEPRE."applications");
		$apptypes = $tab = '';
		foreach($arrapptypes as $apptype) {
			$apptypes .= $tab.$apptype['type'];
			$tab = "\t";
		}
		exit("UC_STATUS_OK|".UC_SERVER_VERSION."|".UC_SERVER_RELEASE."|".UC_CHARSET."|".UC_DBCHARSET."|".$apptypes);
	}

	function _random($length, $numeric = 0) {
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

	function _secrandom($length, $numeric = 0, $strong = false) {
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
			return $strong ? false : $this->_random($length, $numeric);
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
	
	function _generate_key() {
		$random = $this->_secrandom(32);
		$info = md5($_SERVER['SERVER_SOFTWARE'].$_SERVER['SERVER_NAME'].(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '').$_SERVER['SERVER_PORT'].$_SERVER['HTTP_USER_AGENT'].time());
		$return = array();
		for($i=0; $i<32; $i++) {
			$return[$i] = $random[$i].$info[$i];
		}
		return implode('', $return);
	}

	function _format_notedata($notedata) {
		$arr = array();
		foreach($notedata as $key => $note) {
			$arr[$note['appid']] = $note;
		}
		return $arr;
	}

	function _writelog($action, $extra = '') {
		$log = dhtmlspecialchars('UCenterAdministrator'."\t".$this->onlineip."\t".$this->time."\t$action\t$extra");
		$logfile = UC_ROOT.'./data/logs/'.gmdate('Ym', $this->time).'.php';
		if(@filesize($logfile) > 2048000) {
			PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
			$hash = '';
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
			for($i = 0; $i < 4; $i++) {
				$hash .= $chars[mt_rand(0, 61)];
			}
			@rename($logfile, UC_ROOT.'./data/logs/'.gmdate('Ym', $this->time).'_'.$hash.'.php');
		}
		file_put_contents($logfile, "<?PHP exit;?>\t".str_replace(array('<?', '?>', '<?php'), '', $log)."\n", FILE_APPEND | LOCK_EX);
	}

}

?>