<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: memory_driver_yac.php 27635 2017-02-02 17:02:46Z NaiXiaoxIN $
 */
if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class memory_driver_file {

	public $cacheName = 'File';
	public $enable;
	public $path;
	public $cacheData;

	public function env() {
		return true;
	}

	public function init($config) {
		$this->path = $config['server'].'/';
		if($config['server']) {
			$this->enable = is_dir(DISCUZ_ROOT.$this->path);
			if(!$this->enable) {
				dmkdir(DISCUZ_ROOT.$this->path);
				$this->enable = is_dir(DISCUZ_ROOT.$this->path);
			}
		} else {
			$this->enable = false;
		}
	}
	
	private function cachefile($key) {
		return str_replace('_', '/', $key).'/'.$key;
	}
	
	private function _get($key) {
		$file = DISCUZ_ROOT.$this->path.$this->cachefile($key).'.php';
		if(!file_exists($file)) {			
			return false;
		}
		$data = null;
		@include_once $file;
		if($data !== null) {
			if($data['exp'] && $data['exp'] < TIMESTAMP) {		
				return false;
			} else {
				return $data['data'];
			}
		} else {
			return false;
		}
	}
	
	public function get($key) {
		if(!isset($this->cacheData[$key])) {
			$this->cacheData[$key] = $this->_get($key);
		}
		return $this->cacheData[$key];
	}

	public function set($key, $value, $ttl = 0) {
		$file = DISCUZ_ROOT.$this->path.$this->cachefile($key).'.php';
		dmkdir(dirname($file));
		$data = array(
		    'exp' => $ttl ? TIMESTAMP + $ttl : 0,
		    'data' => $value,
		);
		file_put_contents($file, "<?php\n\$data = ".var_export($data, 1).";\n");
		$this->cacheData[$key] = $value;
		return true;
	}

	public function rm($key) {
		return @unlink(DISCUZ_ROOT.$this->path.$this->cachefile($key));
	}
	
	private function dir_clear($dir) {		
		if($directory = @dir($dir)) {
			while($entry = $directory->read()) {
				$filename = $dir.'/'.$entry;
				if($entry != '.' && $entry != '..') {
					if(is_file($filename)) {
						@unlink($filename);
					} else {
						$this->dir_clear($filename);
						@rmdir($filename);
					}
				}
			}
			$directory->close();			
		}
	}

	public function clear() {
		return $this->dir_clear(DISCUZ_ROOT.$this->path);
	}

	public function inc($key, $step = 1) {
		$old = $this->get($key);
		if (!$old) {
			return false;
		}
		return $this->set($key, $old + $step);
	}

	public function dec($key, $step = 1) {
		$old = $this->get($key);
		if (!$old) {
			return false;
		}
		return $this->set($key, $old - $step);
	}

}
