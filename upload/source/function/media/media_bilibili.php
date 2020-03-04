<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('bilibili.com/video/av', 'bilibili.tv/video/av');

function media_bilibili($url, $width, $height) {
	if(preg_match("/https?:\/\/(m.|www.|)bilibili.(com|tv)\/video\/av(\d+)/i", $url, $matches)) {
		$vid = $matches[3];
		$flv = '';
		$iframe = 'https://player.bilibili.com/player.html?aid='.$vid;
		$imgurl = '';
	}
	return array($flv, $iframe, $url, $imgurl);
}
