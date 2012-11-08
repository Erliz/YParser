<?php
/**
 * Класс, создающий базовые возможности работы с БД. Надстройка на \д DbSimple
 */
class Simple {
	/**
	 * Объект, работающий с текущим соединением
	 *
	 * @var DbSimple_Generic_Database
	 */
	private static $dbObject;
	
	/**
	 * Объект, работающий с текущим соединением
	 *
	 * @var DbSimple_Generic_Database
	 */
	protected $DB;
	
	/*protected function databaseErrorHandler($message, $info)
		{
			if (!error_reporting()) return;
			echo "SQL Error: $message<br><pre>"; print_r($info); echo "</pre>";
			exit();
		}*/

	protected function __construct(){
		if (!self::$dbObject){
			self::$dbObject = DbSimple_Generic::connect('mysql://' . DB_USER_NAME . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME);
		}
		$this->DB = self::$dbObject;
		$this->DB->query('SET NAMES UTF8');
		$this->DB->setIdentPrefix(DB_PREFIX);
		//$this->DB->setErrorHandler($this->databaseErrorHandler());
	}
	
	/**
	 * Создает соединение для статических функций
	 *
	 * @return DbSimple_Generic_Database
	 */
	public static function createConnection(){
		if (!self::$dbObject){
			self::$dbObject = DbSimple_Generic::connect('mysql://' . DB_USER_NAME . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME);
			self::$dbObject->query('SET NAMES UTF8');
			self::$dbObject->setIdentPrefix(DB_PREFIX);
		}
		return self::$dbObject;
	}

	/**
	 * Создает соединение для статических функций
	 *
	 * @return DbSimple_Generic_Database
	 */
	public static function clearConnection(){
		self::$dbObject = false;
	}
	
	/**
	 * Преобразует поля из БД в свойства объекта
	 * возвращает массив вида Свойство объекта => Значение
	 *
	 * @param array $data
	 * @param array $objectRef
	 * 
	 * @return array
	 */
	protected static function mappingFromDb($data, $objectRef){
		$result = array();
		foreach ($objectRef as $key => $value){
			$result[$key] = @$data[$value] ? $data[$value] : '';
		}
		return $result;
	}
}
?>