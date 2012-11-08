<?php
/**
 * Класс для получения переменных
 * применяется для того, чтобы всегда получать релевантные переменные
 * @author Vladimir Kuptcov
 * @version 1.0
 */
class Params{
	/**
	 * Получение строковых переменных
	 *
	 * @param string $sParamName имя переменной, получаемой из массива $_REQUEST
	 * @param string $sDefault значение по умолчанию
	 * @param boolean $urlDecode декодировать ли значение переменной
	 * @return string
	 */
	static function loadParam($sParamName, $sDefault = '', $urlDecode = true){
		$sParamValue = trim(@$_REQUEST[$sParamName]);
		if (!$sParamValue) $sParamValue = $sDefault;
		if ($urlDecode){
			$sParamValue = urldecode($sParamValue);
		}
		return $sParamValue;
	}
	
	
	/**
	 * Получение целочисленных беззнаковых переменных
	 *
	 * @param string $sParamName имя переменной из $_REQUEST
	 * @param integer $nDefault значение по умолчанию
	 * @return integer
	 */
	static function loadIntParam($sParamName, $nDefault = 0){
		return (is_numeric(@$_REQUEST[$sParamName]) ?  $_REQUEST[$sParamName] : $nDefault);
	}
	
	/**
	 * Получает переменную, проверяет ее наличие в массиве допустимых значений
	 *
	 * @param string $sParamName имя переменной в массиве $_REQUEST
	 * @param mixed $sDefault значение по умолчанию
	 * @param array $enum массив допустимых значений
	 * @return mixed
	 */
	static function loadEnum($sParamName, $sDefault, $enum){
		$sParamValue = trim(@$_REQUEST[$sParamName]);
		if (!in_array($sParamValue, $enum)){
			$sParamValue = $sDefault;
		}
		return $sParamValue;
	}
}
?>