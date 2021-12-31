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
$removetime = TIMESTAMP - $_G['setting']['smstimelimit'] + 86400;

foreach(C::t('common_smslog')->fetch_all_by_dateline($removetime, '<=') as $smslog) {
	C::t('common_smslog')->insert_archiver($smslog);
	C::t('common_smslog')->delete($smslog['smslogid']);
}