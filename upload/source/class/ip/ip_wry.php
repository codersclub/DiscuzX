<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: ip_wry.php 3915 2019-12-03 12:00:00Z opensource $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class ip_wry_init_exception extends Exception {}

class ip_wry {


	private static $instance = null;
	private $fp = null;
	private $ipbegin = null;
	private $ipAllNum = null;

	private function __construct() {
		$ipdatafile = constant("DISCUZ_ROOT").'./data/ipdata/wry.dat';
		$this->fp = fopen($ipdatafile, 'rb');
		if (!$this->fp) {
			throw new ip_wry_init_exception();
		}
		if(!($DataBegin = fread($this->fp, 4)) || !($DataEnd = fread($this->fp, 4)) ) throw new ip_wry_init_exception();
		$this->ipbegin = implode('', unpack('L', $DataBegin));
		if($this->ipbegin < 0) $this->ipbegin += pow(2, 32);
		$ipend = implode('', unpack('L', $DataEnd));
		if($ipend < 0) $ipend += pow(2, 32);
		$this->ipAllNum = ($ipend - $this->ipbegin) / 7 + 1;
	}

	function __destruct() {
		if ($this->fp) {
			@fclose($this->fp);
		}
	}

	public static function getInstance() {
		if (!self::$instance) {
			try {
				self::$instance = new ip_wry();
			} catch (Exception $e) {
				return null;
			}
		}
		return self::$instance;
	}

	public function convert($ip) {
		$ip = explode('.', $ip);
		$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

		$BeginNum = $ip2num = $ip1num = 0;
		$ipAddr1 = $ipAddr2 = '';
		$EndNum = $this->ipAllNum;

		while($ip1num > $ipNum || $ip2num < $ipNum) {
			$Middle= intval(($EndNum + $BeginNum) / 2);

			fseek($this->fp, $this->ipbegin + 7 * $Middle);
			$ipData1 = fread($this->fp, 4);
			if(strlen($ipData1) < 4) {
				return '- System Error';
			}
			$ip1num = implode('', unpack('L', $ipData1));
			if($ip1num < 0) $ip1num += pow(2, 32);

			if($ip1num > $ipNum) {
				$EndNum = $Middle;
				continue;
			}

			$DataSeek = fread($this->fp, 3);
			if(strlen($DataSeek) < 3) {
				return '- System Error';
			}
			$DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
			fseek($this->fp, $DataSeek);
			$ipData2 = fread($this->fp, 4);
			if(strlen($ipData2) < 4) {
				return '- System Error';
			}
			$ip2num = implode('', unpack('L', $ipData2));
			if($ip2num < 0) $ip2num += pow(2, 32);

			if($ip2num < $ipNum) {
				if($Middle == $BeginNum) {
					return '- Unknown';
				}
				$BeginNum = $Middle;
			}
		}

		$ipFlag = fread($this->fp, 1);
		if($ipFlag == chr(1)) {
			$ipSeek = fread($this->fp, 3);
			if(strlen($ipSeek) < 3) {
				return '- System Error';
			}
			$ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
			fseek($this->fp, $ipSeek);
			$ipFlag = fread($this->fp, 1);
		}

		if($ipFlag == chr(2)) {
			$AddrSeek = fread($this->fp, 3);
			if(strlen($AddrSeek) < 3) {
				return '- System Error';
			}
			$ipFlag = fread($this->fp, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($this->fp, 3);
				if(strlen($AddrSeek2) < 3) {
					return '- System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($this->fp, $AddrSeek2);
			} else {
				fseek($this->fp, -1, SEEK_CUR);
			}

			while(($char = fread($this->fp, 1)) != chr(0))
			$ipAddr2 .= $char;

			$AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
			fseek($this->fp, $AddrSeek);

			while(($char = fread($this->fp, 1)) != chr(0))
			$ipAddr1 .= $char;
		} else {
			fseek($this->fp, -1, SEEK_CUR);
			while(($char = fread($this->fp, 1)) != chr(0))
			$ipAddr1 .= $char;

			$ipFlag = fread($this->fp, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($this->fp, 3);
				if(strlen($AddrSeek2) < 3) {
					return '- System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($this->fp, $AddrSeek2);
			} else {
				fseek($this->fp, -1, SEEK_CUR);
			}
			while(($char = fread($this->fp, 1)) != chr(0))
			$ipAddr2 .= $char;
		}

		if(preg_match('/http/i', $ipAddr2)) {
			$ipAddr2 = '';
		}
		$ipaddr = "$ipAddr1 $ipAddr2";
		$ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
		$ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
		$ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
		if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
			$ipaddr = '- Unknown';
		}

		return '- '.diconv($ipaddr, 'GBK');
	}

}
?>