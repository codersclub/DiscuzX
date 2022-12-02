<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id$
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


class sms {

	// DISCUZ_CLASS_SMS_TYPE 表示宽泛业务类型, 以便区分是否需要验证
	// 验证类短信为 0 , 通知类短信为 1
	const DISCUZ_CLASS_SMS_TYPE_SECCODE = 0;
	const DISCUZ_CLASS_SMS_TYPE_MESSAGE = 1;

	// DISCUZ_CLASS_SMS_SRVTYPE 表示特定业务类型, 以便快速查询对应业务
	// 系统级手机号码验证业务为 1, 系统级短消息通知业务为 2
	// 第三方业务可设置为 0 或不低于 10000 的整数
	const DISCUZ_CLASS_SMS_SRVTYPE_OTHERSRV = 0;
	const DISCUZ_CLASS_SMS_SRVTYPE_SECCHECK = 1;
	const DISCUZ_CLASS_SMS_SRVTYPE_NEWSLETT = 2;

	// DISCUZ_CLASS_SMS_ERROR 表示短信发送中的错误信息
	// 当前步骤正常返回 0
	// 发送时间短于设置返回 -1, 单号码发送次数风控规则不通过返回 -2, 万号段风控规则不通过返回 -3, 全局风控规则不通过返回 -4, 无可用网关返回 -5, 网关接口文件不存在返回 -6,
	// 网关接口类不存在返回 -7, 短信功能已被关闭返回 -8, 短信网关私有异常返回 -9
	const DISCUZ_CLASS_SMS_ERROR_NOWNOERR = 0;
	const DISCUZ_CLASS_SMS_ERROR_TIMELESS = -1;
	const DISCUZ_CLASS_SMS_ERROR_NUMLIMIT = -2;
	const DISCUZ_CLASS_SMS_ERROR_MILLIMIT = -3;
	const DISCUZ_CLASS_SMS_ERROR_GLBLIMIT = -4;
	const DISCUZ_CLASS_SMS_ERROR_CTFSMSGW = -5;
	const DISCUZ_CLASS_SMS_ERROR_CTFGWNME = -6;
	const DISCUZ_CLASS_SMS_ERROR_CTFGWCLS = -7;
	const DISCUZ_CLASS_SMS_ERROR_SMSDISAB = -8;
	const DISCUZ_CLASS_SMS_ERROR_SMSGWERR = -9;

	// DISCUZ_CLASS_SMS_VERIFY 代表短信验证结果
	// 未通过校验为 0, 通过校验为 1
	const DISCUZ_CLASS_SMS_VERIFY_FAIL = 0;
	const DISCUZ_CLASS_SMS_VERIFY_PASS = 1;

	// DISCUZ_CLASS_SMSGW_GWTYPE 代表网关类型
	// 消息短信为 1 , 模板短信为 0
	const DISCUZ_CLASS_SMSGW_GWTYPE_MSG = 0;
	const DISCUZ_CLASS_SMSGW_GWTYPE_TPL = 1;

	// 校验用户获取到的验证码是否正确
	public static function verify($uid, $svctype, $secmobicc, $secmobile, $seccode, $updateverify = 1) {
		// 限制时间区间, 默认 86400 秒
		$smstimelimit = getglobal('setting/smstimelimit');
		$smstimelimit = $smstimelimit > 0 ? $smstimelimit : 86400;
		$lastsend = C::t('common_smslog')->get_lastsms_by_uumm($uid, $svctype, $secmobicc, $secmobile);
		$result = self::DISCUZ_CLASS_SMS_VERIFY_FAIL;
		if($seccode == $lastsend['content'] && !$lastsend['verify'] && time() - $lastsend['dateline'] < $smstimelimit) {
			$result = self::DISCUZ_CLASS_SMS_VERIFY_PASS;
		}
		if($updateverify) {
			C::t('common_smslog')->update($lastsend['smslogid'], array('verify' => 1));
		}
		return $result;
	}

	public static function send($uid, $smstype, $svctype, $secmobicc, $secmobile, $content, $force) {
		// 获取用户基础信息
		$time = time();
		$ip = getglobal('clientip');
		$port = getglobal('remoteport');

		// 判断短信功能是否开启、用户是否允许发送短信
		$check = self::check($uid, $secmobicc, $secmobile, $time, $ip, $port, $force);
		if($check < 0) {
			self::log($smstype, $svctype, 0, $check, $uid, $secmobicc, $secmobile, $time, $ip, $port, $content);
			return $check;
		}

		// 获取对应的短信网关
		$smsgw = self::smsgw($smstype, $secmobicc);
		if($smsgw < 0) {
			self::log($smstype, $svctype, 0, $smsgw, $uid, $secmobicc, $secmobile, $time, $ip, $port, $content);
			return $smsgw;
		}

		// 加载网关文件进行发送
		$output = self::output($smsgw, $uid, $smstype, $svctype, $secmobicc, $secmobile, $content);
		self::log($smstype, $svctype, 0, $output, $uid, $secmobicc, $secmobile, $time, $ip, $port, $content);
		return $output;
	}

	protected static function check($uid, $secmobicc, $secmobile, $time, $ip, $port, $force) {
		// $ip 和 $port 是为后续可能新增的基于 IP 地址的风控所做的预留
		// 具体是否实现需要看上线之后的具体情况
		if(!getglobal('setting/smsstatus')) {
			return self::DISCUZ_CLASS_SMS_ERROR_SMSDISAB;
		}

		if(!$force) {
			// 限制时间区间, 默认 86400 秒
			$smstimelimit = getglobal('setting/smstimelimit');
			$smstimelimit = $smstimelimit > 0 ? $smstimelimit : 86400;
			// 单用户/单号码短信限制时间区间内总量, 默认 5 条
			$smsnumlimit = getglobal('setting/smsnumlimit');
			$smsnumlimit = $smsnumlimit > 0 ? $smsnumlimit : 5;
			// 单用户/单号码短信时间间隔, 默认 300 秒
			$smsinterval = getglobal('setting/smsinterval');
			$smsinterval = $smsinterval > 0 ? $smsinterval : 300;
			// 万号段短信限制时间区间内总量, 默认 20 条
			$smsmillimit = getglobal('setting/smsmillimit');
			$smsmillimit = $smsmillimit > 0 ? $smsmillimit : 20;
			// 全局短信限制时间区间内总量, 默认 1000 条
			$smsglblimit = getglobal('setting/smsglblimit');
			$smsglblimit = $smsglblimit > 0 ? $smsglblimit : 1000;

			// 单号码/单用户风控规则
			$ut = C::t('common_smslog')->get_sms_by_ut($uid, $smstimelimit);
			$mmt = C::t('common_smslog')->get_sms_by_mmt($secmobicc, $secmobile, $smstimelimit);
			if($time - $ut[0]['dateline'] < $smsinterval || $time - $mmt[0]['dateline'] < $smsinterval) {
				return self::DISCUZ_CLASS_SMS_ERROR_TIMELESS;
			}
			if(count($ut) > $smsnumlimit || count($mmt) > $smsnumlimit) {
				return self::DISCUZ_CLASS_SMS_ERROR_NUMLIMIT;
			}

			// 万号段风控规则
			$lastmilion = C::t('common_smslog')->count_sms_by_milions_mmt($secmobicc, $secmobile, $smstimelimit);
			if($lastmilion > $smsmillimit) {
				return self::DISCUZ_CLASS_SMS_ERROR_MILLIMIT;
			}

			// 全局风控规则
			$globalsend = C::t('common_smslog')->count_sms_by_time($smstimelimit);
			if($globalsend > $smsglblimit) {
				return self::DISCUZ_CLASS_SMS_ERROR_GLBLIMIT;
			}
		}

		return self::DISCUZ_CLASS_SMS_ERROR_NOWNOERR;
	}

	protected static function smsgw($smstype, $secmobicc) {
		$smsgwlist = C::t('common_smsgw')->fetch_all_gw_avaliable();
		foreach($smsgwlist as $key => $value) {
			if(array_search($secmobicc, explode(',', $value['sendrule'])) !== false) {
				if($smstype == self::DISCUZ_CLASS_SMS_TYPE_MESSAGE && $value['type'] == self::DISCUZ_CLASS_SMSGW_GWTYPE_TPL) {
					continue;
				}
				$smsgw = $value;
			}
		}

		if(isset($smsgw)) {
			return $smsgw;
		} else {
			return self::DISCUZ_CLASS_SMS_ERROR_CTFSMSGW;
		}

	}

	protected static function output($smsgw, $uid, $smstype, $svctype, $secmobicc, $secmobile, $content) {
		global $_G;
		$efile = explode(':', $smsgw['class']);
		if(is_array($efile) && count($efile) > 1) {
			$smsgwfile = in_array($efile[0], $_G['setting']['plugins']['available']) ? DISCUZ_ROOT.'./source/plugin/'.$efile[0].'/smsgw/smsgw_'. $efile[1] . '.php' : '';
		} else {
			$smsgwfile = DISCUZ_ROOT.'./source/class/smsgw/smsgw_' . $smsgw['class'] . '.php';
		}

		if($smsgwfile) {
			include($smsgwfile);
			$classname = 'smsgw_' . ((is_array($efile) && count($efile) > 1) ? $efile[1] : $smsgw['class']);
			if(class_exists($classname)) {
				$class = new $classname();
				$class->parameters = dunserialize($smsgw['parameters']);
				$result = $class->send($uid, $smstype, $svctype, $secmobicc, $secmobile, array('content' => $content));
			} else {
				$result = self::DISCUZ_CLASS_SMS_ERROR_CTFGWCLS;
			}
		} else {
			$result = self::DISCUZ_CLASS_SMS_ERROR_CTFGWNME;
		}

		if($result < 0 && ($result == self::DISCUZ_CLASS_SMS_ERROR_CTFGWCLS || $result == self::DISCUZ_CLASS_SMS_ERROR_CTFGWNME)) {
			$data = array('available' => '0');
			C::t('common_smsgw')->update($smsgw['smsgwid'], $data);
		}

		return $result;
	}

	protected static function log($smstype, $svctype, $smsgw, $status, $uid, $secmobicc, $secmobile, $time, $ip, $port, $content = '') {
		return C::t('common_smslog')->insert(array('smstype' => $smstype, 'svctype' => $svctype, 'smsgw' => $smsgw, 'status' => $status, 'uid' => $uid, 'secmobicc' => $secmobicc, 'secmobile' => $secmobile, 'dateline' => $time, 'ip' => $ip, 'port' => $port, 'content' => $content));
	}

}