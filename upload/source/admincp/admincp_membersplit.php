<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_membersplit.php 29851 2012-05-02 02:18:40Z zhangguosheng $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();


if(!$operation) {
	$operation = 'check';
}
loadcache(array('membersplitdata', 'userstats'));
if(!empty($_G['cache']['membersplitstep'])) {
	cpmsg('membersplit_split_in_backstage', 'action=membersplit&operation=check', 'loadingform');
}

if($operation == 'check') {
	shownav('founder', 'nav_membersplit');
	showsubmenu('membersplit');
	/*search={"nav_membersplit":"action=membersplit","nav_membersplit":"action=membersplit&operation=check"}*/
	showtips('membersplit_check_tips');
	/*search*/
	showformheader('membersplit&operation=manage');
	showtableheader('membersplit_table_orig');
	$membercount = $_G['cache']['userstats']['totalmembers'];

	showsubtitle(array('','','membersplit_count', 'membersplit_lasttime_check'));


	if($membercount < 50000) {
		$msg = $lang['membersplit_without_optimization'];
	} else {
		$msg = empty($_G['cache']['membersplitdata']) ? $lang['membersplit_has_no_check'] : dgmdate($_G['cache']['membersplitdata']['dateline']);
	}
	showtablerow('', '', array('','', number_format($membercount), $msg));

	if($membercount >= 50000) {
		showsubmit('membersplit_check_submit', 'membersplit_check');
	}
	showtablefooter();
	showformfooter();

} else if($operation == 'manage') {
	shownav('founder', 'nav_membersplit');
	if(!submitcheck('membersplit_split_submit', 1)) {
		showsubmenu('membersplit');
		/*search={"nav_membersplit":"action=membersplit","nav_membersplit":"action=membersplit&operation=check"}*/
		showtips('membersplit_tips');
		/*search*/
		showformheader('membersplit&operation=manage');
		showtableheader('membersplit_table_orig');

		if($_G['cache']['membersplitdata'] && $_G['cache']['membersplitdata']['dateline'] > TIMESTAMP - 86400) {
			$zombiecount = $_G['cache']['membersplitdata']['zombiecount'];
		} else {
			$zombiecount = C::t('common_member')->count_zombie();
			if($zombiecount >= 1) {
				$zombiecount--;// 考虑到用户分表操作的最后一个用户可能也是数据库中最后一个用户，因此在此固定扣除一个用户，保证最后一个用户不会被移动到归档表，从而避免最后一个用户被移动到归档表导致用户主表自增值异常的问题
			}
			savecache('membersplitdata', array('zombiecount' => $zombiecount, 'dateline' => TIMESTAMP));
		}
		$membercount = $_G['cache']['userstats']['totalmembers'];
		$percentage = round($zombiecount/$membercount, 4)*100;

		showsubtitle(array('','','membersplit_count', 'membersplit_combie_count', 'membersplit_splitnum'));
		showtablerow('', '',
				array('','', number_format($membercount), number_format($zombiecount).'('.$percentage.'%) ', '<input name="splitnum" value="200" type="text" class="txt"/>'));

		if($percentage > 0) {
			showsubmit('membersplit_split_submit', 'membersplit_archive');
		}
		showtablefooter();
		showformfooter();

	} else {
		$step = intval($_GET['step'])+1;
		$splitnum = max(10, intval($_GET['splitnum']));
		if(!$_GET['nocheck'] && $step == 1 && !C::t('common_member_archive')->check_table()) {
			cpmsg('membersplit_split_check_table', 'action=membersplit&operation=rebuildtable&splitnum='.$splitnum, 'loadingform', array());
			cpmsg('', 'action=membersplit&operation=manage', 'error');
		}
		if(!C::t('common_member')->split($splitnum)) {
			cpmsg('membersplit_split_succeed', 'action=membersplit&operation=manage', 'succeed');
		}
		cpmsg('membersplit_split_doing', 'action=membersplit&operation=manage&membersplit_split_submit=1&step='.$step.'&splitnum='.$splitnum, 'loadingform', array('num' => $step*$splitnum));
	}
} else if($operation == 'rebuildtable') {
	$step = intval($_GET['step']);
	$splitnum = max(10, intval($_GET['splitnum']));
	$ret = C::t('common_member_archive')->rebuild_table($step);
	if($ret === false) {
		cpmsg('membersplit_split_check_table_done', 'action=membersplit&operation=manage&membersplit_split_submit=1&nocheck=1&splitnum='.$splitnum, 'loadingform');
	} else if($ret === true) {
		cpmsg('membersplit_split_checking_table', 'action=membersplit&operation=rebuildtable&splitnum='.$splitnum.'&step='.($step+1), 'loadingform', array('step' => $step+1));
	} else {
		cpmsg('membersplit_split_check_table_fail', 'action=membersplit&operation=manage&splitnum='.$splitnum, 'error', array('tablename' => $ret));
	}
}

?>