<?php
/**
 *  Возвращает различные html объекты
*/
class Controls
{
	//Возвращает чекбокс
	static function writeCheckox($sName, $bChecked = false, $sText = '', $value = '', $other = ''){
		if ($bChecked) $sSel = 'checked'; else $sSel = '';
		$sOutput = '<input type="checkbox" ' . $sSel . ' name="' . $sName . '" id="' . $sName . '" value="'. htmlspecialchars($value) .'" ' . $other . ' />'.$sText;
		return($sOutput);
	}

	static function writeTextBox($sName, $sValue = '', $nSize = 0, $nMaxLength = 0, $other = ''){
		$sResult = '<input type="text" name="' . $sName . '" id="' . $sName . '" value="' . htmlspecialchars($sValue) . '"';
		if ($nSize != 0) $sResult = $sResult  . ' size="' . $nSize . '"';
		if ($nMaxLength != 0) $sResult = $sResult . ' maxlength="' . $nMaxLength . '"';
		$sResult = $sResult . ' ' . $other . '/>';
		return($sResult);
	}
	
	static function writePassword($sName, $sValue = '', $nSize = 0, $nMaxLength = 0, $other = ''){
		$sResult = '<input type="password" name="' . $sName . '" id="' . $sName . '" value="' . htmlspecialchars($sValue) . '"';
		if ($nSize != 0) $sResult = $sResult  . ' size="' . $nSize . '"';
		if ($nMaxLength != 0) $sResult = $sResult . ' maxlength="' . $nMaxLength . '"';
		$sResult = $sResult . ' ' . $other . '/>';
		return($sResult);
	}

	static function writeHidden($sName, $sValue = ''){
		$sOutput = '<input type="hidden" name="' . $sName . '" id="' . $sName . '" value="' . htmlspecialchars($sValue) . '" />';
		return($sOutput);
	}
	
	/*Возвращает селект по переданному массиву
	Если массив вида array(Index => arrays(sText => '', sIndex => ''), )
	то надо еще передать название поля, которое д. отображаться в контейнере и название для параметра value*/
	static function writeSelect($sName, $arrayOfParametrs, $keyToCheck = '', $sText = '', $sIndex = '', $other = ''){
		$sResult = '<select name="' . $sName . '" id="' . $sName . '" ' . $other . '>' . "\n";
		foreach ($arrayOfParametrs as $key => $value)
		{
			//Если переданный массив многомерный - то вытаскиваем из него Текст и значение
			if (is_array($value))
			{
				$key = $value[$sIndex];
				$value = $value[$sText];
			}
			$sResult .= '<option value="' . $key . '"' . ($key == $keyToCheck ? ' selected' : '') . '>' . htmlspecialchars($value) . '</option>' . "\n";
		}
		return $sResult .= '</select>';
	}
	
	static function writeText($sName, $sValue, $sOther){
		return '<textarea id="' . $sName . '" name="' . $sName . '" ' . $sOther . '>' . htmlspecialchars($sValue) . '</textarea>';
	}
}
?>