<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_template.php 36284 2016-12-12 00:47:50Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class template {

	var $subtemplates = array();
	var $csscurmodules = '';
	var $replacecode = array('search' => array(), 'replace' => array());
	var $blocks = array();
	var $language = array();
	var $file = '';
	var $filetype = 'htm';
	var $debug = 0;

	function parse_template($tplfile, $templateid = 1, $tpldir = '', $file = '', $cachefile = '', $postparse = null) {
		$basefile = basename(DISCUZ_ROOT.$tplfile, '.'.$this->filetype);
		$file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.CURMODULE;
		$this->file = $file;

		if($fp = @fopen(DISCUZ_ROOT.$tplfile, 'r')) {
			$template = filesize(DISCUZ_ROOT.$tplfile) ? fread($fp, filesize(DISCUZ_ROOT.$tplfile)) : '';
			fclose($fp);
		} elseif($fp = @fopen($filename = substr(DISCUZ_ROOT.$tplfile, 0, -(strlen($this->filetype) + 1)).'.php', 'r')) {
			$template = filesize($filename) ? fread($fp, filesize($filename)) : '';
			$template = $this->getphptemplate($template);
			fclose($fp);
		} else {
			$tpl = $tpldir.'/'.$file.'.'.$this->filetype;
			$tplfile = $tplfile != $tpl ? $tpl.', '.$tplfile : $tplfile;
			$this->error('template_notfound', $tplfile);
		}

		if($this->debug) {
			$template = $this->insertdebugmsg($template, $tplfile);
		}

		$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

		$headerexists = preg_match("/{(sub)?template\s+[\w:\/]+?header\}/", $template);
		$this->subtemplates = array();
		for($i = 1; $i <= 3; $i++) {
			if(strexists($template, '{subtemplate')) {
				$template = preg_replace_callback("/[\n\r\t]*(\<\!\-\-)?\{subtemplate\s+([a-z0-9_:\/]+)\}(\-\-\>)?[\n\r\t]*/is", array($this, 'parse_template_callback_loadsubtemplate_2'), $template);
			}
		}

		$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
		$template = preg_replace("/\/\*\*\{(.+?)\}\*\//s", "{\\1}", $template);
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
		$template = preg_replace_callback("/\{lang\s+(.+?)\}/is", array($this, 'parse_template_callback_languagevar_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{block\/(\d+?)\}[\n\r\t]*/i", array($this, 'parse_template_callback_blocktags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{blockdata\/(\d+?)\}[\n\r\t]*/i", array($this, 'parse_template_callback_blockdatatags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{ad\/(.+?)\}[\n\r\t]*/i", array($this, 'parse_template_callback_adtags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{ad\s+([a-zA-Z0-9_\[\]]+)\/(.+?)\}[\n\r\t]*/i", array($this, 'parse_template_callback_adtags_21'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{date\((.+?)\)\}[\n\r\t]*/i", array($this, 'parse_template_callback_datetags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{avatar\((.+?)\)\}[\n\r\t]*/i", array($this, 'parse_template_callback_avatartags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{eval\}\s*(\<\!\-\-)*(.+?)(\-\-\>)*\s*\{\/eval\}[\n\r\t]*/is", array($this, 'parse_template_callback_evaltags_2'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/is", array($this, 'parse_template_callback_evaltags_1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{csstemplate\}[\n\r\t]*/is", array($this, 'parse_template_callback_loadcsstemplate'), $template);
		$template = str_replace("{LF}", "<?=\"\\n\"?>", $template);
		$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\s(or|\?\?)\s([a-zA-Z0-9\']+)\}/s", "{echo \\1 ?? \\3}", $template);
		$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
		$template = preg_replace_callback("/\{hook\/(\w+?)(\s+(.+?))?\}/i", array($this, 'parse_template_callback_hooktags_13'), $template);
		$template = preg_replace_callback("/$var_regexp/s", array($this, 'parse_template_callback_addquote_1'), $template);
		$template = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", array($this, 'parse_template_callback_addquote_1'), $template);

		$headeradd = $headerexists ? "hookscriptoutput('$basefile');" : '';
		if(!empty($this->subtemplates)) {
			$headeradd .= "\n0\n";
			foreach($this->subtemplates as $fname) {
				$headeradd .= "|| checktplrefresh('$tplfile', '$fname', ".time().", '$templateid', '$cachefile', '$tpldir', '$file')\n";
			}
			$headeradd .= ';';
		}

		if(!empty($this->blocks)) {
			$headeradd .= "\n";
			$headeradd .= "block_get('".implode(',', $this->blocks)."');";
		}

		if($cachefile) {
			$template = "<? if(!defined('IN_DISCUZ')) exit('Access Denied'); {$headeradd}?>\n$template";
		}

		$template = preg_replace_callback("/[\n\r\t]*\{template\s+([a-z0-9_:\/]+)\}[\n\r\t]*/is", array($this, 'parse_template_callback_stripvtags_template1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is", array($this, 'parse_template_callback_stripvtags_template1'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", array($this, 'parse_template_callback_stripvtags_echo1'), $template);

		$template = preg_replace_callback("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/is", array($this, 'parse_template_callback_stripvtags_if123'), $template);
		$template = preg_replace_callback("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", array($this, 'parse_template_callback_stripvtags_elseif123'), $template);
		$template = preg_replace("/\{else\}/i", "<? } else { ?>", $template);
		$template = preg_replace("/\{\/if\}/i", "<? } ?>", $template);

		$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'parse_template_callback_stripvtags_loop12'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", array($this, 'parse_template_callback_stripvtags_loop123'), $template);
		$template = preg_replace("/\{\/loop\}/i", "<? } ?>", $template);

		$template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
		if(!empty($this->replacecode)) {
			$template = str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
		}
		$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

		if($cachefile && !@$fp = fopen(DISCUZ_ROOT.$cachefile, 'c')) {
			$this->error('directory_notfound', dirname(DISCUZ_ROOT.$cachefile));
		}

		$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", array($this, 'parse_template_callback_transamp_0'), $template);
		$template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/is", array($this, 'parse_template_callback_stripscriptamp_12'), $template);
		$template = preg_replace_callback("/[\n\r\t]*\{block\s+([a-zA-Z0-9_\[\]']+)\}(.+?)\{\/block\}/is", array($this, 'parse_template_callback_stripblock_12'), $template);
		$template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
		$template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
		if($this->debug) {
			$template = preg_replace_callback("/\<script[\s\w=\/\"]*?\>.+?\<\/script\>/is", array($this, 'parse_template_callback_scriptdebugconvert_0'), $template);
		}
		if(is_callable($postparse)) {
			$template = $postparse($template);
		}

		if(!($cachefile && $fp && flock($fp, LOCK_EX) && ftruncate($fp, 0) && fwrite($fp, $template) && fflush($fp) && flock($fp, LOCK_UN) && fclose($fp))) {
			return $template;
		}
	}

	function parse_template_callback_loadsubtemplate_2($matches) {
		return $this->loadsubtemplate($matches[2]);
	}

	function parse_template_callback_languagevar_1($matches) {
		return $this->languagevar($matches[1]);
	}

	function parse_template_callback_blocktags_1($matches) {
		return $this->blocktags($matches[1]);
	}

	function parse_template_callback_blockdatatags_1($matches) {
		return $this->blockdatatags($matches[1]);
	}

	function parse_template_callback_adtags_1($matches) {
		return $this->adtags($matches[1]);
	}

	function parse_template_callback_adtags_21($matches) {
		return $this->adtags($matches[2], $matches[1]);
	}

	function parse_template_callback_datetags_1($matches) {
		return $this->datetags($matches[1]);
	}

	function parse_template_callback_avatartags_1($matches) {
		return $this->avatartags($matches[1]);
	}

	function parse_template_callback_evaltags_2($matches) {
		return $this->evaltags($matches[2]);
	}

	function parse_template_callback_evaltags_1($matches) {
		return $this->evaltags($matches[1]);
	}

	function parse_template_callback_loadcsstemplate($matches) {
		return $this->loadcsstemplate();
	}

	function parse_template_callback_hooktags_13($matches) {
		return $this->hooktags($matches[1], isset($matches[3]) ? $matches[3] : '');
	}

	function parse_template_callback_addquote_1($matches) {
		return $this->addquote('<?='.$matches[1].'?>');
	}

	function parse_template_callback_stripvtags_template1($matches) {
		return $this->stripvtags('<? include template(\''.$matches[1].'\'); ?>');
	}

	function parse_template_callback_stripvtags_echo1($matches) {
		return $this->stripvtags('<? echo '.$this->echopolyfill($matches[1]).'; ?>');
	}

	function parse_template_callback_stripvtags_if123($matches) {
		return $this->stripvtags($matches[1].'<? if('.$matches[2].') { ?>'.$matches[3]);
	}

	function parse_template_callback_stripvtags_elseif123($matches) {
		return $this->stripvtags($matches[1].'<? } elseif('.$matches[2].') { ?>'.$matches[3]);
	}

	function parse_template_callback_stripvtags_loop12($matches) {
		return $this->stripvtags($this->looptags($matches[1], $matches[2]));
	}

	function parse_template_callback_stripvtags_loop123($matches) {
		return $this->stripvtags($this->looptags($matches[1], $matches[2], $matches[3]));
	}

	function parse_template_callback_transamp_0($matches) {
		return $this->transamp($matches[0]);
	}

	function parse_template_callback_stripscriptamp_12($matches) {
		return $this->stripscriptamp($matches[1], $matches[2]);
	}

	function parse_template_callback_stripblock_12($matches) {
		return $this->stripblock($matches[1], $matches[2]);
	}

	function parse_template_callback_scriptdebugconvert_0($matches) {
		return $this->scriptdebugconvert($matches[0]);
	}

	function languagevar($var) {
		$vars = explode(':', $var);
		$isplugin = count($vars) == 2;
		if(!$isplugin) {
			!isset($this->language['inner']) && $this->language['inner'] = array();
			$langvar = &$this->language['inner'];
		} else {
			!isset($this->language['plugin'][$vars[0]]) && $this->language['plugin'][$vars[0]] = array();
			$langvar = &$this->language['plugin'][$vars[0]];
			$var = &$vars[1];
		}
		if(!isset($langvar[$var])) {
			$this->language['inner'] = lang('template');
			if(!$isplugin) {

				if(defined('IN_MOBILE')) {
					$mobiletpl = getglobal('mobiletpl');
					list($path) = explode('/', str_replace($mobiletpl[IN_MOBILE].'/', '', $this->file));
				} else {
					list($path) = explode('/', $this->file);
				}
				$path = $path == 'common' ? '' : $path.'/';

				foreach(lang($path.'template') as $k => $v) {
					$this->language['inner'][$k] = $v;
				}

				if(defined('IN_MOBILE')) {
					foreach(lang('mobile/template') as $k => $v) {
						$this->language['inner'][$k] = $v;
					}
				}
			} else {
				global $_G;
				if(empty($_G['config']['plugindeveloper'])) {
					loadcache('pluginlanguage_template');
				} elseif(!isset($_G['cache']['pluginlanguage_template'][$vars[0]]) && preg_match("/^[a-z]+[a-z0-9_]*$/i", $vars[0])) {
					if(@include(DISCUZ_ROOT.'./data/plugindata/'.$vars[0].'.lang.php')) {
						$_G['cache']['pluginlanguage_template'][$vars[0]] = $templatelang[$vars[0]];
					} else {
						loadcache('pluginlanguage_template');
					}
				}
				$this->language['plugin'][$vars[0]] = $_G['cache']['pluginlanguage_template'][$vars[0]];
			}
		}
		if(isset($langvar[$var])) {
			return $langvar[$var];
		} else {
			return '!'.$var.'!';
		}
	}

	function blocktags($parameter) {
		$bid = intval(trim($parameter));
		$this->blocks[] = $bid;
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--BLOCK_TAG_$i-->";
		$this->replacecode['replace'][$i] = "<?php block_display('$bid');?>";
		return $search;
	}

	function blockdatatags($parameter) {
		$bid = intval(trim($parameter));
		$this->blocks[] = $bid;
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--BLOCKDATA_TAG_$i-->";
		$this->replacecode['replace'][$i] = "";
		return $search;
	}

	function adtags($parameter, $varname = '') {
		$parameter = stripslashes($parameter);
		$parameter = preg_replace("/(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)/s", "{\\1}", $this->addquote($parameter));
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--AD_TAG_$i-->";
		$this->replacecode['replace'][$i] = "<?php ".(!$varname ? 'echo ' : '$'.$varname.'=')."adshow(\"$parameter\");?>";
		return $search;
	}

	function datetags($parameter) {
		$parameter = stripslashes($parameter);
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--DATE_TAG_$i-->";
		$this->replacecode['replace'][$i] = "<?php echo dgmdate($parameter);?>";
		return $search;
	}

	function avatartags($parameter) {
		$parameter = stripslashes($parameter);
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--AVATAR_TAG_$i-->";
		$this->replacecode['replace'][$i] = "<?php echo avatar($parameter);?>";
		return $search;
	}

	function evaltags($php) {
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
		$this->replacecode['replace'][$i] = $this->debug ? '<? '.preg_replace(array('/^L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*\-\-\>/', '/\<\!\-\-L\d+[\w\.\/]*$/', '/^\s*\<\!\-\-/', '/\-\-\>\s*$/'), '', $php).'?>' : "<? $php?>";
		return $search;
	}

	function hooktags($hookid, $key = '') {
		global $_G;
		$i = count($this->replacecode['search']);
		$this->replacecode['search'][$i] = $search = "<!--HOOK_TAG_$i-->";
		$dev = '';
		if(isset($_G['config']['plugindeveloper']) && $_G['config']['plugindeveloper'] == 2) {
			$dev = "echo '<hook>[".($key ? 'array' : 'string')." $hookid".($key ? '/\'.'.$key.'.\'' : '')."]</hook>';";
		}
		$key = $key != '' ? "[$key]" : '';
		$this->replacecode['replace'][$i] = "<?php {$dev}if(!empty(\$_G['setting']['pluginhooks']['$hookid']$key)) echo \$_G['setting']['pluginhooks']['$hookid']$key;?>";
		return $search;
	}

	function stripphpcode($type, $code) {
		$this->phpcode[$type][] = $code;
		return '{phpcode:'.$type.'/'.(count($this->phpcode[$type]) - 1).'}';
	}

	function loadsubtemplate($file) {
		$tplfile = template($file, 0, '', 1);
		$filename = DISCUZ_ROOT.$tplfile;
		if((file_exists($filename) && is_readable($filename) && ($content = implode('', file($filename)))) || (file_exists(substr($filename, 0, -4).'.php') && is_readable(substr($filename, 0, -4).'.php') && ($content = $this->getphptemplate(implode('', file(substr($filename, 0, -4).'.php')))))) {
			$this->subtemplates[] = $tplfile;
			return $this->debug ? $this->insertdebugmsg($content, $tplfile) : $content;
		} else {
			return '<!-- '.$file.' -->';
		}
	}

	function getphptemplate($content) {
		$pos = strpos($content, "\n");
		return $pos !== false ? substr($content, $pos + 1) : $content;
	}

	function loadcsstemplate() {
		global $_G;
		$file = file(DISCUZ_ROOT.'./data/cache/style_'.STYLEID.'_module.css');
		$scripts = array(STYLEID.'_common');
		$content = $this->csscurmodules = '';
		$content = implode('', is_array($file) ? $file : array());
		$content = preg_replace_callback("/\[(.+?)\](.*?)\[end\]/is", array($this, 'loadcsstemplate_callback_cssvtags_12'), $content);
		if($this->csscurmodules) {
			$this->csscurmodules = preg_replace(array('/\s*([,;:\{\}])\s*/', '/[\t\n\r]/', '/\/\*.+?\*\//'), array('\\1', '',''), $this->csscurmodules);
			if(file_put_contents(DISCUZ_ROOT.'./data/cache/style_'.STYLEID.'_'.$_G['basescript'].'_'.CURMODULE.'.css', $this->csscurmodules, LOCK_EX) === false) {
				exit('Can not write to cache files, please check directory ./data/ and ./data/cache/ .');
			}
			$scripts[] = STYLEID.'_'.$_G['basescript'].'_'.CURMODULE;
		}
		$scriptcss = '';
		foreach($scripts as $css) {
			$scriptcss .= '<link rel="stylesheet" type="text/css" href="{$_G[\'setting\'][\'csspath\']}'.$css.'.css?{VERHASH}" />';
		}
		$scriptcss .= '{if $_G[\'uid\'] && isset($_G[\'cookie\'][\'extstyle\']) && strpos($_G[\'cookie\'][\'extstyle\'], TPLDIR) !== false}<link rel="stylesheet" id="css_extstyle" type="text/css" href="{$_G[\'cookie\'][\'extstyle\']}/style.css" />{elseif $_G[\'style\'][\'defaultextstyle\']}<link rel="stylesheet" id="css_extstyle" type="text/css" href="{$_G[\'style\'][\'defaultextstyle\']}/style.css" />{/if}';
		if(isset($_G['config']['output']['css4legacyie']) && $_G['config']['output']['css4legacyie']) {
			$scriptcss .= '<!--[if IE]><link rel="stylesheet" type="text/css" href="'.$_G['setting']['csspath'].STYLEID.'_iefix'.'.css?{VERHASH}" /><![endif]-->';
		}
		return $scriptcss;
	}

	function loadcsstemplate_callback_cssvtags_12($matches) {
		return $this->cssvtags($matches[1], $matches[2]);
	}

	function cssvtags($param, $content) {
		global $_G;
		$modules = explode(',', $param);
		foreach($modules as $module) {
			$module .= '::'; //fix notice
			list($b, $m) = explode('::', $module);
			if($b && $b == $_G['basescript'] && (!$m || $m == CURMODULE)) {
				$this->csscurmodules .= $content;
				return;
			}
		}
		return;
	}

	function looptags($param1, $param2, $param3 = '') {
		if(preg_match("/^\<\?\=\\\$.+?\?\>$/s", $param1)) {
			$exprtemp = $param1;
			$return = '<? if(isset('.$param1.') && is_array('.$param1.')) ';
		} else {
			$exprtemp = '$l_'.random(8);
			$return = '<? '.$exprtemp.' = '.$param1.';if(is_array('.$exprtemp.')) ';
		}
		if($param3) {
			$return .= 'foreach('.$exprtemp.' as '.$param2.' => '.$param3.') { ?>';
		} else {
			$return .= 'foreach('.$exprtemp.' as '.$param2.') { ?>';
		}
		return $return;
	}

	function echopolyfill($str) {
		$str = str_replace(' or ', ' ?? ', $str);
		if(strpos($str, ' ?? ') !== false && version_compare(PHP_VERSION, '7.0', '<')) {
			$str = preg_replace('/^(.+)\s\?\?\s(.+)$/', "isset(\\1) ? (\\1) : (\\2)", $str);
		}
		return $str;
	}

	function transamp($str) {
		$str = str_replace('&', '&amp;', $str);
		$str = str_replace('&amp;amp;', '&amp;', $str);
		return $str;
	}

	function addquote($var) {
		return str_replace("\\\"", "\"", preg_replace_callback("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", array($this, 'addquote_exec'), $var));
	}

	function addquote_exec($matches) {
		return is_numeric($matches[1]) ? '['.$matches[1].']' : "['".$matches[1]."']";
	}


	function stripvtags($expr, $statement = '') {
		$expr = str_replace('\\\"', '\"', preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
		if($this->debug) {
			$expr = preg_replace('/\<\!\-\-L\d+[\w\.\/]*\-\-\>/', '', $expr);
		}
		$statement = str_replace('\\\"', '\"', $statement);
		return $expr.$statement;
	}

	function stripscriptamp($s, $extra) {
		$s = str_replace('&amp;', '&', $s);
		return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
	}

	function stripblock($var, $s) {
		$var = $this->addquote($var);
		$s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
		preg_match_all("/<\?=(.+?)\?>/", $s, $constary);
		$constadd = '';
		$constary[1] = array_unique($constary[1]);
		foreach($constary[1] as $const) {
			$constadd .= '$__'.$const.' = '.$const.';';
		}
		$s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
		$s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
		$s = str_replace('<?', "\nEOF;\n", $s);
		$s = str_replace("\nphp ", "\n", $s);
		return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
	}

	function scriptdebugconvert($str) {
		return preg_replace('/\<\!\-\-L(\d+[\w\.\/]*)\-\-\>/', '/**L\1*/', $str);
	}

	function insertdebugmsg($str, $filename) {
		$startmsg = '<!-- BEGIN '.$filename.' -->';
		$endmsg = '<!-- END '.$filename.' -->';
		$count = 2;
		$debuglevel = $this->debug;
		$str = preg_replace_callback('/\n(\t*)/', function($matches) use (&$count, $filename, $debuglevel){
			if($debuglevel > 1) {
				return "\n".$matches[1].'<!--L'.$count++.$filename.'-->';
			} else {
				return "\n".$matches[1].'<!--L'.$count++.'-->';
			}
		}, $str);
		return $startmsg.$str.$endmsg;
	}

	function error($message, $tplname) {
		discuz_error::template_error($message, $tplname);
	}

}

?>