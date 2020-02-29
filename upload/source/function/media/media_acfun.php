<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('www.acfun.cn/v/ac', 'www.acfun.tv/v/ac');

function media_acfun($url, $width, $height) {
	if(preg_match("/https?:\/\/www.acfun.(cn|tv)\/v\/ac(\d+)/i", $url, $matches)) {
		$vid = $matches[2];
		$flv = '';
		$iframe = 'https://www.acfun.cn/player/ac'.$vid;
		$imgurl = '';
	}
	return array($flv, $iframe, $url, $imgurl);
}
