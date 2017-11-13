<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('v.youku.com/v_show/');

function media_youku($url, $width, $height) { 
	$ctx = stream_context_create(array('http' => array('timeout' => 10)));
	if(preg_match("/^https?:\/\/v.youku.com\/v_show\/id_([^\/]+)(.html|)/i", $url, $matches)) {
	    	$flv = 'https://player.youku.com/player.php/sid/'.$matches[1].'/v.swf';
	    	$iframe = 'https://player.youku.com/embed/'.$matches[1];
	    	if(!$width && !$height) {
	    		$api = 'http://v.youku.com/player/getPlayList/VideoIDS/'.$matches[1];
	    		$str = stripslashes(file_get_contents($api, false, $ctx));
	    		if(!empty($str) && preg_match("/\"logo\":\"(.+?)\"/i", $str, $image)) {
	    			$url = substr($image[1], 0, strrpos($image[1], '/')+1);
	    			$filename = substr($image[1], strrpos($image[1], '/')+2);
	    			$imgurl = $url.'0'.$filename;
	    		}
	    	}
	}
	return array($flv, $iframe, $url, $imgurl);
}