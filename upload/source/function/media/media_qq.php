<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('v.qq.com/x/page/');

function media_qq($url, $width, $height) {
	if(preg_match("/https?:\/\/v.qq.com\/x\/page\/([^\/]+)(.html|)/i", $url, $matches)) {
		$vid = explode(".html", $matches[1]);
		$flv = 'https://imgcache.qq.com/tencentvideo_v1/playerv3/TPout.swf?vid='.$vid[0];
		$iframe = 'https://v.qq.com/iframe/player.html?vid='.$vid[0];
	}
	return array($flv, $iframe, $url, $imgurl);
}