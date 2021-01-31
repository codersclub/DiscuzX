<?php

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

class extends_data {

	public static $id;
	public static $title;
	public static $image;
	public static $icon;
	public static $poptype;
	public static $popvalue;
	public static $clicktype;
	public static $clickvalue;
	public static $field;

	public static $list = array();
	public static $page = 1;
	public static $perpage = 50;

	public function __construct() {

	}

	public static function common() {

	}

	public static function insertrow() {
		self::$list[] = array(
			'id' => self::$id,
			'title' => self::$title,
			'image' => self::$image,
			'icon' => self::$icon,
			'poptype' => self::$poptype,
			'popvalue' => self::$popvalue,
			'clicktype' => self::$clicktype,
			'clickvalue' => self::$clickvalue,
			'fields' => self::$field,
		);
		self::$field = array();
	}

	public static function field($id, $icon, $value) {
		self::$field[] = array('id' => $id, 'icon' => $icon, 'value' => $value);
	}

	public static function output() {
		return array(
			__CLASS__ => array('page' => self::$page, 'perpage' => self::$perpage, 'list' => self::$list)
		);
	}
}
?>