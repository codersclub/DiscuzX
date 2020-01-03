<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_session.php 28051 2012-02-21 10:36:56Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_session extends discuz_table
{
	public function __construct() {

		$this->_table = 'common_session';
		$this->_pk    = 'sid';

		$this->_pre_cache_key = 'common_session_';
		$this->_cache_ttl = -1;

		parent::__construct();

		// 依赖Set, Hash, SortedSet数据类型，依赖LUA脚本，不能启用cluster
		$this->_allowmem = $this->_allowmem && !C::memory()->gotcluster && C::memory()->gotset && C::memory()->gothash && C::memory()->goteval && C::memory()->gotsortedset;
	}

	// 在memory启用的情况下，直接从memory中读取
	public function fetch($sid, $ip = false, $uid = false) {
		if(empty($sid)) {
			return array();
		}
		$this->checkpk();
		if ($this->_allowmem) {
			$session = $this->get_data_by_pk($sid);
		} else {
			$session = parent::fetch($sid);
		}
		if($session && $ip !== false && $ip != "{$session['ip']}") {
			$session = array();
		}
		if($session && $uid !== false && $uid != $session['uid']) {
			$session = array();
		}
		return $session;
	}

	/*
	 * memory下，基于以下几个SortedSet进行集合运算
	 * 		idx_invisible_$invisible: SortedSet, member是sid, score是lastactivity
	 * 		idx_uid_group_0: uid=0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_uid_group_1: uid>0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_lastactivity: SortedSet, member是sid, score是lastactivity
	 */
	public function fetch_member($ismember = 0, $invisible = 0, $start = 0, $limit = 0) {
		if ($ismember < 1 || $ismember > 2) $ismember = 0;
		if ($invisible < 1 || $invisible > 2) $invisible = 0;

		if (!$this->_allowmem) {
			$sql = array();
			if($ismember === 1) {
				$sql[] = 'uid > 0';
			} elseif($ismember === 2) {
				$sql[] = 'uid = 0';
			}
			if($invisible === 1) {
				$sql[] = 'invisible = 1';
			} elseif($invisible === 2) {
				$sql[] = 'invisible = 0';
			}
			$wheresql = !empty($sql) && is_array($sql) ? ' WHERE '.implode(' AND ', $sql) : '';
			$sql = 'SELECT * FROM %t '.$wheresql.' ORDER BY lastactivity DESC'.DB::limit($start, $limit);
			return DB::fetch_all($sql, array($this->_table), $this->_pk);
		}

		list($ss, $ee) = $this->get_start_and_end($start, $limit);
		($invisible == 2) ? $inv_idx = 0 : $inv_idx = 1;
		($ismember == 2) ? $uid_idx = 0 : $uid_idx = 1;

		if ($ismember == 0 && $invisible == 0) { // 使用idx_lastactivity
			$sids = memory('zrevrange', 'idx_lastactivity', $ss, $ee, $this->_pre_cache_key);
		} elseif ($ismember == 0) { // 使用idx_invisible_xx
			$sids = memory('zrevrange', 'idx_invisible_' . $inv_idx, $ss, $ee, $this->_pre_cache_key);
		} elseif ($invisible == 0) { // 使用idx_uid_group_0/1
			$sids = memory('zrevrange', 'idx_uid_group_' . $uid_idx, $ss, $ee, $this->_pre_cache_key);
		} else { // 对 idx_invisible_$inv_idx与idx_uid_group_$uid_idx 求交集
			global $_G;
			$temp_uniq = substr(md5(substr(TIMESTAMP, 0, -3).substr($_G['config']['security']['authkey'], 3, -3)), 1, 8);
			$script = <<<LUA
			local prefix = ARGV[1]
			local inv_idx = ARGV[2]
			local uid_idx = ARGV[3]
			local out_surfix = ARGV[4]
			local start = ARGV[5]
			local stop = ARGV[6]
			local out_hash = prefix..'invisible_uid_'..out_surfix
			redis.call('ZINTERSTORE', out_hash, 2, prefix..'idx_invisible_'..inv_idx, prefix..'idx_uid_group_'..uid_idx, 'AGGREGATE', 'MIN')
			local sids = redis.call('ZREVRANGE', out_hash, start, stop)
			redis.call('DEL', out_hash)
			return sids
			LUA;
			$sids = memory('eval', $script, array($inv_idx, $uid_idx, $temp_uniq, $ss, $ee), "fetch_member", $this->_pre_cache_key);
		}
		$result = array();
		foreach ($sids as $sid) {
			$result[] = $this->get_data_by_pk($sid);
		}
		return $result;
	}

	/*
	 * memory下，基于 idx_invisible_$invisible: SortedSet, member是sid, score是lastactivity
	 */
	public function count_invisible($type = 1) {
		if (!$this->_allowmem) {
			return DB::result_first('SELECT COUNT(*) FROM %t WHERE invisible=%d', array($this->_table, $type));
		}

		return memory('zcard', 'idx_invisible_' . $type, $this->_pre_cache_key);
	}

	/*
	 * memory时三种情况：
	 * 		1. 无条件(所有行)：直接使用idx_lastactivity的大小
	 * 		2. uid = 0: 使用 idx_uid_group_0 的大小
	 * 		3. uid > 0: 使用 idx_uid_group_1 的大小
	 */
	public function count($type = 0) {
		if (!$this->_allowmem) {
			$condition = $type == 1 ? ' WHERE uid>0 ' : ($type == 2 ? ' WHERE uid=0 ' : '');
			return DB::result_first("SELECT count(*) FROM ".DB::table($this->_table).$condition);
		}

		switch ($type) {
			case 1:
				return memory('zcard', 'idx_uid_group_1', $this->_pre_cache_key);
			case 2:
				return memory('zcard', 'idx_uid_group_0', $this->_pre_cache_key);
			default:
				return memory('zcard', 'idx_lastactivity', $this->_pre_cache_key);
		}
	}

	/*
	 * 基于以下几种情况删除：
	 * 		$sid，基于key直接删除: $prefix$sid
	 * 		$lastactivity < $onlinehold: 基于 idx_lastactivity sortedset筛选
	 * 		$uid = 0 and $ip = clientip and $lastactivity > $guestspan: 基于 idx_uid_group_0 sortedset筛选
	 * 		$uid = $session[uid]: 基于uid set
	 * 删除后要更新所有索引
	 * 		idx_ip_$ipaddress: SortedSet，member是sid, score是lastactivity
	 * 		idx_invisible_$invisible: SortedSet, member是sid, score是lastactivity
	 * 		idx_fid_$fid: SortedSet, member是sid, score是lastactivity
	 * 		idx_uid_group_0: uid=0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_uid_group_1: uid>0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_lastactivity: SortedSet, member是sid, score是lastactivity
	 * 		idx_uid_$uid: Set, member是sid
	 * 		
	 */
	public function delete_by_session($session, $onlinehold, $guestspan) {
		if(empty($session) || !is_array($session)) return;
		$onlinehold = time() - $onlinehold;
		$guestspan = time() - $guestspan;

		if (!$this->_allowmem) {
			$session = daddslashes($session);
			//当前用户的sid
			$condition = " sid='{$session[sid]}' ";
			//过期的 session
			$condition .= " OR lastactivity<$onlinehold ";
			//频繁的同一ip游客
			$condition .= " OR (uid='0' AND ".DB::field('ip', $session['ip'])." AND lastactivity>$guestspan) ";
			//当前用户的uid
			$condition .= $session['uid'] ? " OR (uid='{$session['uid']}') " : '';
			DB::delete('common_session', $condition);
		}

		// 以下脚本，按上面SQL的条件逐个删除，记录删除的所有数据，之后利用删除的数据更新索引。
		// 在脚本执行的过程中，前面删除过的数据，后面就获取不到数据，因此不会被加入到 rs 中，因此 rs 中是没有重复的数据的
		global $_G;
		$temp_uniq = substr(md5(substr(TIMESTAMP, 0, -3).substr($_G['config']['security']['authkey'], 3, -3)), 1, 8);
		$script = <<<LUA
		local rs = {}
		local prefix = ARGV[1]
		local sid = ARGV[2]
		local onlinehold = ARGV[3]
		local guestspan = ARGV[4]
		local userip = ARGV[5]
		local uid = ARGV[6]
		local out_surfix = ARGV[7]
		
		local function getdata(key)
			local data = redis.call("HMGET", key, 'sid', 'ip', 'uid', 'invisible', 'fid')
			if (data[1]) then 
				return data
			else
				return {}
			end
		end
		
		local bysid = getdata(prefix..sid);
		if (#bysid > 0) then
			redis.call("del", prefix..sid)
			rs[#rs + 1] = bysid
		end
		
		-- lastactivity < onlinehold
		local byonlinehold = redis.call("ZRANGEBYSCORE", prefix.."idx_lastactivity", 0, onlinehold + 1);
		for _, sid in ipairs(byonlinehold) do
			local data = getdata(prefix..sid);
			if (#data > 0) then
				redis.call("del", prefix..sid)
				rs[#rs + 1] = data
			end
		end
		
		-- uid = 0 and ip = userip
		local out_hash = prefix..'uid0_ip_'..out_surfix
		redis.call("ZINTERSTORE", out_hash, 2, prefix.."idx_uid_group_0", prefix.."idx_ip_"..userip, 'AGGREGATE', 'MIN')
		-- and lastactivity > guestspan
		local byguestspan = redis.call("ZRANGEBYSCORE", out_hash, guestspan + 1, '+inf')
		for _, sid in ipairs(byguestspan) do
			local data = getdata(prefix..sid);
			if ((#data > 0)) then
				redis.call("del", prefix..sid)
				rs[#rs + 1] = data
			end
		end
		redis.call("DEL", out_hash)
		
		local byuid = redis.call("SMEMBERS", prefix.."idx_uid_"..uid);
		for _, sid in ipairs(byuid) do
			local data = getdata(prefix..sid);
			if (#data > 0) then
				redis.call("del", prefix..sid)
				rs[#rs + 1] = data
			end
		end
		
		for _, row in ipairs(rs) do
			redis.call("ZREM", prefix.."idx_ip_"..row[2], row[1])
			redis.call("ZREM", prefix.."idx_invisible_"..row[4], row[1])
			redis.call("ZREM", prefix.."idx_fid_"..row[5], row[1])
			if (row[3] == '0') then
				redis.call("ZREM", prefix.."idx_uid_group_0", row[1])
			else
				redis.call("ZREM", prefix.."idx_uid_group_1", row[1])
			end
			redis.call("ZREM", prefix.."idx_lastactivity", row[1])
			redis.call("SREM", prefix.."idx_uid_"..row[3], row[1])
		end
		
		return #rs
		LUA;
		memory('eval', $script, array($session['sid'], $onlinehold, $guestspan, $session['ip'], $session['uid'] ? $session['uid'] : -1, $temp_uniq), "delete_by_session", $this->_pre_cache_key);
	}

	// $prefix_idx_uid_$uid: Set
	public function fetch_by_uid($uid) {
		if(empty($uid)) {
			return false;
		}
		if (!$this->_allowmem) {
			DB::fetch_first('SELECT * FROM %t WHERE uid=%d', array($this->_table, $uid));
		}

		$sids = memory('smembers', 'idx_uid_' . $uid, $this->_pre_cache_key);
		foreach ($sids as $sid) {
			return $this->get_data_by_pk($sid); // 返回第一个
		}
		return false;
	}

	// $prefix_idx_uid_$uid: Set
	// $uids 可能是 array，多个uid
	public function fetch_all_by_uid($uids, $start = 0, $limit = 0) {
		if(empty($uids)) {
			return array();
		}
		if (!$this->_allowmem) {
			return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('uid', $uids).DB::limit($start, $limit), array($this->_table), null, 'uid');
		}
		if (!is_array($uids)) {
			$uids = array($uids);
		}
		$sid_rs = array();
		// 合并所有uid相关的sid
		foreach ($uids as $uid) {
			$sids = memory('smembers', 'idx_uid_' . $uid, $this->_pre_cache_key);
			$sid_rs = array_merge($sid_rs, $sids);
		}
		// 处理 $start and $limit
		list($ss, $ee) = $this->get_start_and_end($start, $limit);
		$sid_rs = array_slice($sid_rs, $ss, $ee == -1 ? null : $ee - $ss + 1);
		// 生成结果
		$result = array();
		foreach ($sid_rs as $sid) {
			$result[] = $this->get_data_by_pk($sid);
		}
		return $result;
	}

	// $prefix_idx_uid_$uid: Set
	// 一个UID如果对应多个SID，都更新
	public function update_by_uid($uid, $data) {
		if(!($uid = dintval($uid)) || empty($data) || !is_array($data)) {
			return 0;
		}
		if (!$this->_allowmem) {
			return DB::update($this->_table, $data, DB::field('uid', $uid));
		}
		
		$sids = memory('smembers', 'idx_uid_' . $uid, $this->_pre_cache_key);
		foreach ($sids as $sid) {
			$olddata = $this->get_data_by_pk($sid);
			$data['sid'] = $sid; 	// $data中可能没有sid
			memory('hmset', $sid, $data, 0, $this->_pre_cache_key);
			$this->update_memory_index($sid, $data, $olddata);
		}
	}


	public function update_max_rows($max_rows) {
		// 使用memory时不支持(无需)设置最大行数
		if ($this->_allowmem) {
			return TRUE;
		}
		return DB::query('ALTER TABLE '.DB::table('common_session').' MAX_ROWS='.dintval($max_rows));
	}

	public function clear() {
		// 使用memory时不支持清除操作
		// 现在只有admincp_setting中一处，在调用了update_max_rows之后，可能调用此操作
		// memory中不支持max_rows设置，因此也不在此处支持clear操作
		if ($this->_allowmem) {
			return TRUE;
		}
		return DB::query('DELETE FROM '.DB::table('common_session'));
	}

	// $prefix_idx_fid_$fid: SortedSet, member是sid, score是lastactivity
	public function count_by_fid($fid) {
		$fid = dintval($fid);
		if (!$fid) return 0;
		if ($this->_allowmem) {
			global $_G;
			$temp_uniq = substr(md5(substr(TIMESTAMP, 0, -3).substr($_G['config']['security']['authkey'], 3, -3)), 1, 8);
			$script = <<<LUA
			local prefix = ARGV[1]
			local fid = ARGV[2]
			local out_surfix = ARGV[3]
			local out_hash = prefix..'uid_fid_inv_'..out_surfix
			-- uid > 0 and fid=fid and invisible = 0
			redis.call("ZINTERSTORE", out_hash, 3, prefix.."idx_uid_group_1", prefix.."idx_fid_"..fid, prefix.."idx_invisible_0", 'AGGREGATE', 'MIN')
			local rs = redis.call("ZCARD", out_hash)
			redis.call("DEL", out_hash)
			return rs
			LUA;
			$data = memory('eval', $script, array($fid, $temp_uniq), "count_by_fid", $this->_pre_cache_key);
			return $data;
		} else {
			return DB::result_first('SELECT COUNT(*) FROM '.DB::table('common_session')." WHERE uid>'0' AND fid='$fid' AND invisible='0'");
		}
	}

	// $prefix_idx_fid_$fid: SortedSet, member是sid, score是lastactivity
	public function fetch_all_by_fid($fid, $limit = 12) {
		$fid = dintval($fid);
		if (!$fid) return array();
		if ($this->_allowmem) {
			global $_G;
			$temp_uniq = substr(md5(substr(TIMESTAMP, 0, -3).substr($_G['config']['security']['authkey'], 3, -3)), 1, 8);
			$script = <<<LUA
			local prefix = ARGV[1]
			local fid = ARGV[2]
			local limit = ARGV[3]
			local out_surfix = ARGV[4]
			local out_hash = prefix..'uid_fid_inv_'..out_surfix
			
			-- uid > 0 and fid=fid and invisible = 0
			redis.call("ZINTERSTORE", out_hash, 3, prefix.."idx_uid_group_1", prefix.."idx_fid_"..fid, prefix.."idx_invisible_0", 'AGGREGATE', 'MIN')
			
			local keys = redis.call("zrevrange", out_hash, 0, limit - 1)
			redis.call("DEL", out_hash)
			local rs = {}
			for _, key in ipairs(keys) do
				local row = redis.call("hmget", ARGV[1]..key, "uid", "groupid", "username", "invisible", "lastactivity")
				rs[#rs + 1] = row
			end 
			return rs			
			LUA;
			$data = memory('eval', $script, array($fid, $limit, $temp_uniq), "fetch_by_fid", $this->_pre_cache_key);
			$result = array();
			foreach ($data as $row) {
				$item = array();
				$item['uid'] = $row[0];
				$item['groupid'] = $row[1];
				$item['username'] = $row[2];
				$item['invisible'] = $row[3];
				$item['lastactivity'] = $row[4];
				$result[] = $item;
			}
			return $result;
		} else {
			return DB::fetch_all('SELECT uid, groupid, username, invisible, lastactivity FROM '.DB::table('common_session')." WHERE uid>'0' AND fid='$fid' AND invisible='0' ORDER BY lastactivity DESC".DB::limit($limit));
		}
	}

	// $prefix_idx_ip_$ipaddress: SortedSet，member是sid, score是lastactivity
	public function count_by_ip($ip) {
		if (empty($ip)) return 0;
		if ($this->_allowmem) {
			return memory('zcard', 'idx_ip_' . $ip, $this->_pre_cache_key);
		} else {
			return DB::result_first('SELECT COUNT(*) FROM '.DB::table('common_session')." WHERE ".DB::field('ip', $ip));
		}
	}

	// $prefix_idx_ip_$ipaddress: SortedSet，member是sid, score是lastactivity
	public function fetch_all_by_ip($ip, $start = 0, $limit = 0) {
		if (empty($ip)) return array();
		if ($this->_allowmem) {
			list($ss, $ee) = $this->get_start_and_end($start, $limit);
			$keys = memory('zrevrange', 'idx_ip_' . $ip, $ss, $ee, $this->_pre_cache_key);
			$data = array();
			foreach ($keys as $key) {
				$data[] = $this->get_data_by_pk($key);
			}
			return $data;
		} else {
			return DB::fetch_all('SELECT * FROM %t WHERE ip=%s ORDER BY lastactivity DESC'.DB::limit($start, $limit), array($this->_table, $ip), null);
		}
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		if (!$this->_allowmem) {
			return parent::insert($data, $return_insert_id, $replace, $silent);
		}
		$id = $data['sid'];
		memory('hmset', $id, $data, 0, $this->_pre_cache_key);
		$this->update_memory_index($id, $data);
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if (!$this->_allowmem) {
			return parent::update($val, $data, $unbuffered, $low_priority);
		}
		$olddata = $this->get_data_by_pk($val);
		memory('hmset', $val, $data, 0, $this->_pre_cache_key);
		$this->update_memory_index($val, $data, $olddata);
	}

	/*
	 * 维护索引
	 * 以下几个索引：
	 * 		idx_ip_$ipaddress: SortedSet，member是sid, score是lastactivity
	 * 		idx_invisible_$invisible: SortedSet, member是sid, score是lastactivity
	 * 		idx_fid_$fid: SortedSet, member是sid, score是lastactivity
	 * 		idx_uid_group_0: uid=0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_uid_group_1: uid>0的SortedSet, member是sid, score是lastactivity，
	 * 		idx_lastactivity: SortedSet, member是sid, score是lastactivity
	 * 		idx_uid_$uid: Set, member是sid
	 */
	private function update_memory_index($sid, $newdata, $olddata = array()) {
		if (!empty($olddata) && !isset($olddata['lastactivity'])) { // 如果有原数据，则原数据中必须有lastactivity
			return;
		}
		if (!empty($olddata) && !isset($newdata['lastactivity'])) { // 如果有原数据，但新数据中没有lastactivity，则使用原有的lastactivity
			$newdata['lastactivity'] = $olddata['lastactivity'];
		}
		foreach ($newdata as $col => $value) {
			// 只针对以下建立索引
			if (!in_array($col, array("ip", "uid", "fid", "lastactivity", "invisible"))) continue;
			if (isset($olddata[$col])) { // 从原index中将sid删除				
				if ($olddata[$col] === $value && $olddata['lastactivity'] === $newdata['lastactivity']) { // 新旧值相等，且score也没变，则不处理
					continue;
				}
				switch ($col) {
					case 'ip':
						memory('zrem', "idx_ip_" . $olddata[$col], $sid, 0, $this->_pre_cache_key);
						break;
					case 'lastactivity':
						memory('zrem', 'idx_lastactivity', $sid, 0, $this->_pre_cache_key);
						break;
					case 'fid':
						memory('zrem', "idx_fid_" . $olddata[$col], $sid, 0, $this->_pre_cache_key);
						break;
					case 'invisible':
						memory('zrem', "idx_invisible_" . $olddata[$col], $sid, 0, $this->_pre_cache_key);
						break;
					case 'uid':
						memory('zrem', "idx_uid_group_" . $olddata[$col] == 0 ? '0' : '1', $sid, 0, $this->_pre_cache_key);
						memory('srem', "idx_uid_" . $olddata[$col], $sid, 0, $this->_pre_cache_key);
						break;
					default:
						continue;
				}
			}
			// 在新index中加入sid
			switch ($col) {
				case 'ip':
				case 'fid':
				case 'invisible':
					memory('zadd', 'idx_' . $col . "_" . $value, $sid, $newdata['lastactivity'], $this->_pre_cache_key);
					break;
				case 'lastactivity':
					memory('zadd', 'idx_lastactivity', $sid, $newdata['lastactivity'], $this->_pre_cache_key);
					break;
				case 'uid':
					memory('zadd', "idx_uid_group_" . ($value == 0 ? '0' : '1'), $sid, $newdata['lastactivity'], $this->_pre_cache_key);
					memory('sadd', 'idx_uid_' . $value, $sid, 0, $this->_pre_cache_key);
					break;
				default:
					continue;
			}
		}
	}

	/*
	 * 根据sid返回所有数据
	 */
	private function get_data_by_pk($sid) {
		$data = memory('hgetall', $sid, $this->_pre_cache_key);
		return $data;
	}

	/*
	 * 从$start和$limit计算start和end
	 * 当$limit为0时，$start参数表示limit
	 */
	private function get_start_and_end($start, $limit) {
		$limit = intval($limit > 0 ? $limit : 0);
		$start = intval($start > 0 ? $start : 0);
		if ($start > 0 && $limit > 0) {
			return array($start, $start + $limit - 1);
		} elseif ($limit > 0) {
			return array(0, $limit - 1);
		} elseif ($start > 0) {
			return array(0, $start - 1);
		} else {
			return array(0, -1);
		}
	}
}

?>