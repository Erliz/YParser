<?php
/**
 * Без понятия зачем этот класс...
 */
class PageStorage {
	private static $currentPage;
	private static $linksArray = array();
	private static $pageText;
	private static $queryID;
	private static $nextPage;
	
	public static function getNext(){
		$temp = current(self::$pageList);
		next(self::$pageList);
		return $temp;
	}
	
	public static function newStorage($queryID, array $linksArray, $pageText, $nextPage){
		self::$queryID = $queryID;
		self::$linksArray = $linksArray;
		self::$pageText = $pageText;
		self::$nextPage = $nextPage;
	}
	
	public static function getNextPage(){
		return self::$nextPage;
	}
}
?>