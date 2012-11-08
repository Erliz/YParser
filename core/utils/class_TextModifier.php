<?php
class TextModifier
{
	static function translit($text, $remainingChars = '\._-', $translitSpace = '_'){
		$text = str_replace('ч', 'ch', $text);
		$text = str_replace('ш', 'sh', $text);
		$text = str_replace('щ', 'sh', $text);
		$text = str_replace('ю', 'yu', $text);
		$text = str_replace('я', 'ya', $text);
		$text = str_replace('ъ', '', $text);
		$text = str_replace('ь', '', $text);
		$text = str_replace(' ', $translitSpace, $text);

		$text = strtr(strtolower($text),
					'абвгдеёжзийклмнопрстуфхцыэ',
					'abvgdeejziiklmnoprstufhcie');
		$text = preg_replace('/[^' . $translitSpace . 'a-z0-9' . $remainingChars . ']/','',$text);
		return $text;
	}
	

	// "Активизация" адресов E-mail.
	static function emailActivate($text){
		return preg_replace(
						  '{
    							[\w-.]+             # имя ящика
    							@
    							[\w-]+(\.[\w-]+)*   # имя хоста
  							}xs',
  							'<a href="mailto:$0">$0</a>',
  						$text
  						);
	}
	
	// Заменяет ссылки на их HTML-эквиваленты ("подчеркивает ссылки").
	static function hrefActivate($text) {
		// Функция обратного вызова для preg_replace_callback().
		if (!function_exists('hrefCallback')){
			function hrefCallback($p) {
	  			// Преобразуем спецсимволы в HTML-представление.
  				$name = htmlspecialchars($p[0]);
  				// Если нет протокола, добавляем его в начало строки.  
  				$href = !empty($p[1])? $name : 'http://' . $name;
	  			// Формируем ссылку.
  				return '<a href="' . $href . '">' . $name . '</a>';
			}
		}
  		return preg_replace_callback(
    		'{
      			(?:
        		(\w+://)          # протокол с двумя слэшами
        		|                 # - или -
        		www\.             # просто начинается на www
      			)
      			[\w-]+(\.[\w-]+)*   # имя хоста
      			(?: : \d+)?         # порт (не обязателен)
      			[^<>"\'()\[\]\s]*   # URI (но БЕЗ кавычек и скобок)
      			(?:                 # последний символ должен быть...
          		(?<! [[:punct:]] )  # НЕ пунктуацией
        		| (?<= [-/&+*]     )  # но допустимо окончание на -/&+*
      			)
    		}xis',
    		"hrefCallback",
    	$text);
    }
	
	//Проверяет наличие в тексте русских и английских символов
	//Возвращает true  в случае присутствия символов из нескольких языков
	static function checkMultiLanguage($text){
		$engStringSmall = 'abcdefghigklmnopqrstuvwxyz';
		$engStringBig = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$rusStringSmall = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
		$rusStringBig = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';
		if (strpbrk($text, $engStringSmall) != false){
			return true;
		}
		if (strpbrk($text, $engStringBig) != false){
			return true;
		}
		if (strpbrk($text, $rusStringSmall) != false){
			return true;
		}
		if (strpbrk($text, $rusStringBig) != false){
			return true;
		}
		return false;
	}
}
?>