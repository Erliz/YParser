<?php
class Registry{
	public static $vars;	
	
	public static function set($key, $var) {
		if (isset(self::$vars[$key])) throw new Exception('Unable to set var `'.$key.'`. Already set.');
		self::$vars[$key] = $var;
		return true;
	}
	
	public static function set_f($key, $var){
		self::$vars[$key] = $var;
		return true;
	}
	
	public static function get($key) {
		if (!isset(self::$vars[$key]))	return null;
		return self::$vars[$key];
	}
	
	function remove($var) {
		unset(self::$vars[$key]);
	}	
	
}
?>