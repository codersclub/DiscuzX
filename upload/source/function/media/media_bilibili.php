<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('www.bilibili.com/video/av', 'www.bilibili.tv/video/av');

function media_bilibili($url, $width, $height) {
	if(preg_match("/https?:\/\/www.bilibili.(com|tv)\/video\/av(\d+)/i", $url, $matches)) {
		$vid = $matches[2];
		$flv = '';
		$iframe = 'https://player.bilibili.com/player.html?aid='.$vid;
		$imgurl = '';
	}
	return array($flv, $iframe, $url, $imgurl);
}
