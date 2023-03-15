<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: memory_driver_redis.php 33336 2013-05-29 02:05:10Z andyzheng $
 */
if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/*
 * $_G['setting'] = new memory_setting_array()
 * 由于memory_setting_array继承了ArrayObject，因此可以正常以原来的方式访问，比如 $_G['setting']['memory']
 * 在启用了redis的情况下，这个对象采用lazy + eager load的方式工作：
 * 	1. 默认构造函数不加载任何setting值
 * 	2. 在FIELDS_GROUPS里定义的多个field group，任何一个field的访问，都会自动加载这个field所在的整个group
 * 	3. 对于不在这里定义的field，访问时再加载
 * 对于其它类型的memory，直接加载整个setting
 */
class memory_setting_array implements ArrayAccess {
	private $can_lazy = false;
	public $array = Array();

	const SETTING_KEY = 'setting';
	const FIELDS_GROUPS = array(
		'common_fields' => array(
			'memory', 'ipaccess', 'mobile', 'domain', 'timeoffset', 'dateformat', 'timeformat', 'bbclosed',
			'topicperpage', 'postperpage', 'nocacheheaders', 'seokeywords', 'seodescription', 'forumpicstyle',
			'rssstatus', 'plugins', 'hookscript', 'pluginhooks', 'bbname', 'seotitle', 'indexhot', 'collectionstatus',
			'grid', 'dateconvert', 'moddisplay', 'whosonlinestatus', 'oltimespan', 'maxonlinelist', 'seohead',
			'version', 'showusercard', 'disallowfloat', 'creditnotice', 'creditnames', 'jspath', 'csspath',
			'portalstatus', 'navs', 'groupstatus', 'feedstatus', 'archiver', 'switchwidthauto', 'shortcut', 'topnavs',
			'mynavs', 'showfjump', 'advtype', 'navmns', 'navdms', 'navmn', 'navlogos', 'avatarmethod', 'ucenterurl',
			'connect', 'taskstatus', 'menunavs', 'subnavs', 'search', 'blogstatus', 'albumstatus', 'srchhotkeywords',
			'forumallowside', 'focus', 'site_qq', 'footernavs', 'siteurl', 'sitename', 'icp', 'statcode', 'debug',
			'boardlicensed', 'followstatus', 'disableipnotice', 'rewritestatus', 'ftp', 'visitbanperiods', 'dynavt',
			'cacheindexlife', 'whosonline_contract', 'regname', 'reglinkname', 'autoidselect', 'avatarurl', 'avatarpath',
			'uidlogin', 'secmobilelogin', 'forumstatus', 'friendstatus', 'guidestatus', 'favoritestatus', 'mps', 'mpsid'
		),
		'forumdisplay_fields' => array(
			'group_admingroupids', 'followforumid', 'targetblank', 'allowmoderatingthread', 'threadmaxpages',
			'globalstick', 'recommendthread', 'heatthread', 'verify', 'visitedforums', 'fastpost', 'seccodedata',
			'secqaa', 'creditstransextra', 'extcredits', 'threadplugins', 'leftsidewidth', 'forumseparator',
			'forumdisplaythreadpreview', 'closeforumorderby', 'minpostsize', 'maxpostsize', 'fastsmilies', 'smcols',
			'allowreplybg', 'newbiespan', 'minpostsize_mobile', 'minsubjectsize', 'maxsubjectsize',
		),
		'viewthread_fields' => array(
			'optimizeviews', 'antitheft', 'cachethreadlife', 'close_leftinfo', 'close_leftinfo_userctrl',
			'creditspolicy', 'modratelimit', 'showsettings', 'preventrefresh', 'infosidestatus',
			'threadhotreplies', 'nofilteredpost', 'threadblacklist', 'threadguestlite', 'threadfilternum',
			'ratelogrecord', 'commentnumber', 'sigviewcond', 'lazyload', 'allowattachurl', 'relatedlinkstatus',
			'numbercard', 'repliesrank', 'vtonlinestatus', 'alloweditpost', 'zoomstatus', 'imagemaxwidth',
			'bannedmessages', 'authoronleft', 'profilenode', 'magicstatus', 'starthreshold', 'allowfastreply',
			'sharestatus', 'globalsightml', 'need_avatar', 'need_secmobile', 'need_email', 'need_friendnum'
		)
	);

	public function __construct()
	{
 		$this->can_lazy = C::memory()->goteval && C::memory()->gothash;
		if (!$this->can_lazy) { // 不支持lazy load的时候，直接加载整个数据
			$this->array = memory('get', self::SETTING_KEY);
			foreach ($this->array as $key => $value) {
				if ($value) $this->array[$key] = dunserialize($value);
			}
		}
	}

	public function offsetExists($index)
	{
		if (!array_key_exists($index, $this->array)) {
			return memory('hexists', self::SETTING_KEY, $index);
		}
		return true;
	}

	public function &offsetGet($index)
	{
		$val = $this->array[$index];
		if ($val === null && $this->can_lazy) {
			foreach (self::FIELDS_GROUPS as $group => $fields) {
				if (in_array($index, $fields)) {
					$this->_load_fields($fields, 'setting_' . $group);
					$val = $this->array[$index];
					break;
				}
			}
			if ($val === null) {
				$data = memory('hget', self::SETTING_KEY, $index);
				$val = dunserialize($data);
				$this->offsetSet($index, $val);
			}
		}
		$ret = & $this->array[$index];
		return $ret;
	}

	public function offsetSet($index, $newval)
	{
		if ($newval === null || $newval === false) $newval = Array();
		$this->array[$index] = $newval;
	}

	public function offsetUnset($index)
	{
		unset($this->array[$index]);
	}

	/*
	 * 不支持lazy load的时候，直接整个保存
	 * 支持lazy load的时候，保存为hash，每个field都做serialize
	 */
	public static function save($data)
	{
		$can_lazy = C::memory()->goteval && C::memory()->gothash;
		if ($can_lazy) {
			$newdata = array();
			foreach ($data as $key => $value) {
				$newdata[$key] = serialize($value);
			}
			memory('hmset', self::SETTING_KEY, $newdata);
		} else {
			memory('set', $data);
		}
	}

	private function _load_fields($fields, $shakey)
	{
		$data = memory('eval', false, array(), $shakey);
		if (!$data) {
			$array_def = "local fields = {" . array_reduce($fields, function($carry, $item) {
				return $carry . '"' . $item . '",';
			}) . "} ";
			$script = <<<LUA
			local prefix = ARGV[1]
			return redis.call('hmget', prefix..'setting', unpack(fields))
LUA;
			$data = memory('eval', $array_def . $script, array(), $shakey);
		}
		foreach ($fields as $index => $field) {
			$this->offsetSet($field, dunserialize($data[$index]));
		}
	}

}