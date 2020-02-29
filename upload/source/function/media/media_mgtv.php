<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('www.mgtv.com/b/', 'www.hunantv.com/b/');

function media_mgtv($url, $width, $height) {
	if(preg_match("/https?:\/\/www.(mg|hunan)tv.com\/b\/(\d+)\/(\d+).html/i", $url, $matches)) {
		$vid = $matches[3];
		$flv = 'https://player.mgtv.com/mgtv_v6_out/main.swf?video_id='.$vid;
		$iframe = '';
		$imgurl = '';
	}
	return array($flv, $iframe, $url, $imgurl);
}
