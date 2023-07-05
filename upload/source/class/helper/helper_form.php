<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: helper_form.php 35986 2016-06-06 01:37:04Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class helper_form {


	public static function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
		if(!getgpc($var)) {
			return FALSE;
		} else {
			global $_G;
			// 新增 $allowget = 2 时，验证formhash参数
			if(($allowget && ($allowget !== 2 || (!empty($_GET['formhash']) && $_GET['formhash'] == formhash()))) || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_GET['formhash']) && $_GET['formhash'] == formhash() && empty($_SERVER['HTTP_X_FLASH_VERSION']) && (empty($_SERVER['HTTP_REFERER']) ||
				strncmp($_SERVER['HTTP_REFERER'], 'http://wsq.discuz.com/', 22) === 0 || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
				if(checkperm('seccode')) {
					if($secqaacheck && !check_secqaa($_GET['secanswer'], $_GET['secqaahash'])) {
						showmessage('submit_secqaa_invalid');
					}
					if($seccodecheck && !check_seccode($_GET['seccodeverify'], $_GET['seccodehash'], 0, $_GET['seccodemodid'])) {
						showmessage('submit_seccode_invalid');
					}
				}
				return TRUE;
			} else {
				showmessage('submit_invalid');
			}
		}
	}

	public static function censor($message, $modword = NULL, $return = FALSE, $modasban = TRUE) {
		global $_G;
		$censor = discuz_censor::instance();
		$censor->check($message, $modword);
		// 新增对仅支持禁止关键词的模块在遇到审核关键词时禁止发布相关内容
		// $modasban 用于指示是否支持审核, 支持审核的模块需要设置为 FALSE
		if(($censor->modbanned() && empty($_G['group']['ignorecensor'])) || (($modasban && !empty($_G['setting']['modasban'])) && $censor->modmoderated() && empty($_G['group']['ignorecensor']))) {
			$wordbanned = implode(', ', $censor->words_found);
			if($return) {
				return array('message' => lang('message', 'word_banned', array('wordbanned' => $wordbanned)));
			}
			if(!defined('IN_ADMINCP')) {
				showmessage('word_banned', '', array('wordbanned' => $wordbanned));
			} else {
				cpmsg(lang('message', 'word_banned'), '', 'error', array('wordbanned' => $wordbanned));
			}
		}
		if($_G['group']['allowposturl'] == 0) {
			$urllist = self::get_url_list($message);
			if(is_array($urllist[1])) {
				foreach($urllist[1] as $key => $val) {
					if(!$val = trim($val)) continue;
					if(!iswhitelist($val)) {
						if($return) {
							return array('message' => 'post_url_nopermission');
						}
						showmessage('post_url_nopermission');
					}
				}
			}
		} elseif($_G['group']['allowposturl'] == 2) {
			$message = preg_replace("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.|mailto:|tel:|magnet:)?([^\r\n\[\"']+?))?\](.+?)\[\/url\]/is", '\\5', $message);
		}
		return $message;
	}

	public static function censormod($message) {
		global $_G;
		if($_G['group']['ignorecensor']) {
			return false;
		}
		$modposturl = false;
		if($_G['group']['allowposturl'] == 1) {
			$urllist = self::get_url_list($message);
			if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
				if(!$val = trim($val)) continue;
				if(!iswhitelist($val)) {
					$modposturl = true;
				}
			}
		}
		if($modposturl) {
			return true;
		}

		$censor = discuz_censor::instance();
		$censor->check($message);
		return $censor->modmoderated();
	}

	public static function get_url_list($message) {
		$return = array();

		(strpos($message, '[/img]') || strpos($message, '[/flash]')) && $message = preg_replace("/\[img[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]|\[flash[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", '', $message);
		if(preg_match_all("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^ \[\]\"']+/i", $message, $urllist)) {
			foreach($urllist[0] as $key => $val) {
				$val = trim($val);
				$return[0][$key] = $val;
				if(!preg_match('/^https?:\/\//is', $val)) $val = 'http://'.$val;
				$tmp = parse_url($val);
				$return[1][$key] = $tmp['host'];
				if($tmp['port']){
					$return[1][$key] .= ":{$tmp['port']}";
				}
			}
		}
		return $return;
	}

	public static function updatemoderate($idtype, $ids, $status = 0) {
		$ids = is_array($ids) ? $ids : array($ids);
		if(!$ids) {
			return;
		}
		if(!$status) {
			foreach($ids as $id) {
				C::t('common_moderate')->insert_moderate($idtype, array(
					'id' => $id,
					'status' => 0,
					'dateline' => TIMESTAMP,
				), false, true);
			}
		} elseif($status == 1) {
			C::t('common_moderate')->update_moderate($ids, $idtype, array('status' => 1));
		} elseif($status == 2) {
			C::t('common_moderate')->delete_moderate($ids, $idtype);
		}
	}
}

?>