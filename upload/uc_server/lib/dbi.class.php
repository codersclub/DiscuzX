<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: db.class.php 980 2009-12-22 03:12:49Z zhaoxiongfei $
*/


class ucserver_db {
	var $querynum = 0;
	var $link;
	var $histories;
	var $stmtcache = array();

	var $dbhost;
	var $dbuser;
	var $dbpw;
	var $dbcharset;
	var $pconnect;
	var $tablepre;
	var $time;

	var $goneaway = 5;

	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre='', $time = 0) {
		if (intval($pconnect) === 1) $dbhost = 'p:' . $dbhost; // 前面加p:，表示persistent connection
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpw = $dbpw;
		$this->dbname = $dbname;
		$this->dbcharset = $dbcharset;
		$this->pconnect = $pconnect;
		$this->tablepre = $tablepre;
		$this->time = $time;

		mysqli_report(MYSQLI_REPORT_OFF);

		if(!$this->link = new mysqli($dbhost, $dbuser, $dbpw, $dbname)) {
			$this->halt('Can not connect to MySQL server');
		}

		$this->link->options(MYSQLI_OPT_LOCAL_INFILE, false);

		if($this->version() > '4.1') {
			if($dbcharset) {
				$this->link->set_charset($dbcharset);
			}

			if($this->version() > '5.0.1') {
				$this->link->query("SET sql_mode=''");
			}
		}
	}

	function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return $query ? $query->fetch_array($result_type) : null;
	}

	function result_first($sql) {
		$query = $this->query($sql);
		return $this->result($query, 0);
	}

	function fetch_first($sql) {
		$query = $this->query($sql);
		return $this->fetch_array($query);
	}

	function fetch_all($sql, $id = '') {
		$arr = array();
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$id ? $arr[$data[$id]] = $data : $arr[] = $data;
		}
		return $arr;
	}

	function result_first_stmt($sql, $key = array(), $value = array()) {
		$query = $this->query_stmt($sql, $key, $value);
		return $this->result($query, 0);
	}

	function fetch_first_stmt($sql, $key = array(), $value = array()) {
		$query = $this->query_stmt($sql, $key, $value);
		return $this->fetch_array($query);
	}

	function fetch_all_stmt($sql, $key = array(), $value = array(), $id = '') {
		$arr = array();
		$query = $this->query_stmt($sql, $key, $value);
		while($data = $this->fetch_array($query)) {
			$id ? $arr[$data[$id]] = $data : $arr[] = $data;
		}
		return $arr;
	}

	function cache_gc() {
		$this->query("DELETE FROM {$this->tablepre}sqlcaches WHERE expiry<$this->time");
	}

	function query($sql, $type = '', $cachetime = FALSE) {
		$resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
		if(!($query = $this->link->query($sql, $resultmode)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		$this->querynum++;
		$this->histories[] = $sql;
		return $query;
	}

	function query_stmt($sql, $key = array(), $value = array(), $type = '', $saveprep = FALSE, $cachetime = FALSE) {
		$parse = $this->parse_query($sql, $key, $value);
		if($saveprep && array_key_exists(hash("sha256", $parse[0]), $this->stmtcache)) {
			$stmt = & $this->stmtcache[hash("sha256", $parse[0])];
		} else {
			$stmt = $this->link->prepare($parse[0]);
			$saveprep && $this->stmtcache[hash("sha256", $parse[0])] = & $stmt;
		}
		if(!empty($key)) {
			$stmt->bind_param(...$parse[1]);
		}
		if(!($query = $stmt->execute()) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $parse[0]);
		}
		$this->querynum++;
		$this->histories[] = $parse[0];
		// SELECT 指令返回数组供其他方法使用, 其他情况返回 SQL 执行结果
		return strncasecmp("SELECT", $sql, 6) ? $query : $stmt->get_result();
	}

	function affected_rows() {
		return $this->link->affected_rows;
	}

	function error() {
		return $this->link->error;
	}

	function errno() {
		return $this->link->errno;
	}

	function result($query, $row) {
		if(!$query || $query->num_rows == 0) {
			return null;
		}
		$query->data_seek($row);
		$assocs = $query->fetch_row();
		return $assocs[0];
	}

	function num_rows($query) {
		$query = $query ? $query->num_rows : 0;
		return $query;
	}

	function num_fields($query) {
		return $query ? $query->field_count : 0;
	}

	function free_result($query) {
		return $query ? $query->free() : false;
	}

	function insert_id() {
		return ($id = $this->link->insert_id) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = $query ? $query->fetch_row() : null;
		return $query;
	}

	function fetch_fields($query) {
		return $query ? $query->fetch_field() : null;
	}

	function version() {
		return $this->link->server_info;
	}

	function escape_string($str) {
		return $this->link->escape_string($str);
	}

	function close() {
		return $this->link->close();
	}

	function parse_query($sql, $key = array(), $value = array()) {
		$list = '';
		$array = array();
		if(strpos($sql, '?')) {// 如果SQL存在问号则使用传统匹配方式，KEY顺序与?的顺序保持一致
			foreach ($key as $k => $v) {
				if(in_array($v, array('i', 'd', 's', 'b'))) {
					$list .= $v;
					$array = array_merge($array, (array)$value[$k]);
				}
			}
		} else {// 不存在问号则使用模拟PDO模式，允许在SQL内指定变量名
			preg_match_all("/:([A-Za-z0-9]*?)( |$)/", $sql, $matches);
			foreach ($matches[1] as $match) {
				if(in_array($key[$match], array('i', 'd', 's', 'b'))) {
					$list .= $key[$match];
					$array = array_merge($array, (array)$value[$match]);
					$sql = str_replace(":".$match, "?", $sql);
				}
			}
		}
		return array($sql, array_merge((array)$list, $array));
	}

	function halt($message = '', $sql = '') {
		$error = $this->error();
		$errorno = $this->errno();
		if($errorno == 2006 && $this->goneaway-- > 0) {
			$this->connect($this->dbhost, $this->dbuser, $this->dbpw, $this->dbname, $this->dbcharset, $this->pconnect, $this->tablepre, $this->time);
			$this->query($sql);
		} else {
			$s = '';
			if($message) {
				$s = "<b>UCenter info:</b> $message<br />";
			}
			if($sql) {
				$s .= '<b>SQL:</b>'.htmlspecialchars($sql).'<br />';
			}
			$s .= '<b>Error:</b>'.$error.'<br />';
			$s .= '<b>Errno:</b>'.$errorno.'<br />';
			$s = str_replace(UC_DBTABLEPRE, '[Table]', $s);
			exit($s);
		}
	}
}

?>