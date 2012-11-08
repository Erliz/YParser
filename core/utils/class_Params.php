<?php
/**
 * ����� ��� ��������� ����������
 * ����������� ��� ����, ����� ������ �������� ����������� ����������
 * @author Vladimir Kuptcov
 * @version 1.0
 */
class Params{
	/**
	 * ��������� ��������� ����������
	 *
	 * @param string $sParamName ��� ����������, ���������� �� ������� $_REQUEST
	 * @param string $sDefault �������� �� ���������
	 * @param boolean $urlDecode ������������ �� �������� ����������
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
	 * ��������� ������������� ����������� ����������
	 *
	 * @param string $sParamName ��� ���������� �� $_REQUEST
	 * @param integer $nDefault �������� �� ���������
	 * @return integer
	 */
	static function loadIntParam($sParamName, $nDefault = 0){
		return (is_numeric(@$_REQUEST[$sParamName]) ?  $_REQUEST[$sParamName] : $nDefault);
	}
	
	/**
	 * �������� ����������, ��������� �� ������� � ������� ���������� ��������
	 *
	 * @param string $sParamName ��� ���������� � ������� $_REQUEST
	 * @param mixed $sDefault �������� �� ���������
	 * @param array $enum ������ ���������� ��������
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