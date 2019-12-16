<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class ip_v6wry_init_exception extends Exception {}

/**
 * Modified from class IPDBv6 ( popcorner, MIT License )
 */

class ip_v6wry {
	private static $instance = null;
	public $ipdb,$firstIndex,$indexCount,$offlen;
	public function __construct() {
		$ipdatafile = constant("DISCUZ_ROOT").'./data/ipdata/ipv6wry.dat';
		$this->ipdb = fopen($ipdatafile,'rb');
		if (!$this->ipdb) {
			throw new ip_v6wry_init_exception();
		}
		$this->firstIndex = unpack('V',$this->reader(16,8))[1];
		$this->indexCount = unpack('V',$this->reader(8,8))[1];
		$this->offlen = ord($this->reader(6,1));
	}
	public function __destruct() {
		if($this->ipdb) {
			@fclose($this->ipdb);
		}
	}
	public static function getInstance() {
		if (!self::$instance) {
			try {
				self::$instance = new ip_v6wry();
			} catch (Exception $e) {
				return null;
			}
		}
		return self::$instance;
	}
	public function getstring($offset) {
		fseek($this->ipdb,$offset);
		$flag = 1;
		$return = '';
		while($flag) {
			$i = fread($this->ipdb,1);
			if($i === "\0") {
				$flag = 0;
			} else {
				$return .= $i;
			}
		}
		return $return;
	}
	public function getareaaddr($offset) {
		$byte = ord($this->reader($offset,1));
		if($byte == 1 || $byte == 2) {
			$p = unpack('V',str_pad($this->reader($offset + 1,$this->offlen),4,"\0"))[1];
			return $this->getareaaddr($p);
		} else {
			return $this->getstring($offset);
		}
	}
	public function getaddr($offset) {
		$byte = ord($this->reader($offset,1));
		if($byte == 1) {
			return $this->getaddr(unpack('V',str_pad($this->reader($offset + 1,$this->offlen),4,"\0"))[1]);
		} else {
			$carea = $this->getareaaddr($offset);
			if($byte == 2) {
				$offset += 1 + $this->offlen;
			} else {
				$offset += strlen($carea) + 1;
			}
			$aarea = $this->getareaaddr($offset);
			return [$carea,$aarea];
		}
	}
	public function ipcomp($ip1,$ip2) {
		$ip1a = unpack('v',substr($ip1,-2))[1];
		$ip2a = unpack('v',substr($ip2,-2))[1];
		if($ip1a == $ip2a) {
			if(strlen($ip1)<=2) {
				return 0;
			} else {
				return $this->ipcomp(substr($ip1,0,-2),substr($ip2,0,-2));
			}
		} elseif($ip1a > $ip2a) {
			return 1;
		} else {
			return -1;
		}
	}
	public function reader($offset,$length) {
		fseek($this->ipdb,$offset);
		return fread($this->ipdb,$length);
	}
	public function finder($ip,$l,$r) {
		if($r-$l<=1) {
			return $l;
		}
		$m = intval(($l + $r)/2);
		$o = $this->firstIndex + $m * (8 + $this->offlen);
		$new_ip = $this->reader($o,8);
		if($this->ipcomp($new_ip,$ip)>0) {
			return $this->finder($ip,$l,$m);
		} else {
			return $this->finder($ip,$m,$r);
		}
	}
	public function getipaddr($ip) {
		$ipbinary = inet_pton($ip);
		if($ipbinary == false) {
			return '- Unknown';
		}
		$iprev = strrev($ipbinary);
		$i = $this->finder($iprev,0,$this->indexCount);
		$o = $this->firstIndex + $i * (8 + $this->offlen);
		$output = $this->getaddr(unpack('L',str_pad($this->reader($o + 8,$this->offlen),4,"\0"))[1]);
		return $output;
	}
	public function convert($ip) {
		return '- '.diconv(implode(' ',$this->getipaddr($ip)),'utf-8');
	}
}
?>