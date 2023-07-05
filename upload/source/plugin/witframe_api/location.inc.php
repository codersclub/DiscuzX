<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT . './source/plugin/witframe_api/core.php';

$conf = Lib\Site::Discuz_GetConf($_G['setting']['siteuniqueid']);

if (!$conf) {
	cpmsg('&#x65E0;&#x6CD5;&#x8BBF;&#x95EE; WitFrame!&#xFF0C;&#x8BF7;&#x68C0;&#x67E5;&#x7F51;&#x7EDC;');
}

$ret = Lib\Site::Discuz_LoginWit($_G['setting']['siteuniqueid']);

if (!$ret) {
	cpmsg('&#x65E0;&#x6CD5;&#x8BBF;&#x95EE; WitFrame!&#xFF0C;&#x8BF7;&#x68C0;&#x67E5;&#x7F51;&#x7EDC;');
}

?>
<div class="infobox">
	<h4 class="infotitle2"><a href="<?php echo $ret['url']; ?>" target="_blank">&#x70B9;&#x6B64;&#x8BBF;&#x95EE; WitFrame!</a></h4>
</div>