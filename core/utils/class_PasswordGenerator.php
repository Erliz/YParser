<?php
/**
 * Класс, предназначенный для генерации паролей
 * 
 * @author Vladimir Kuptcov
 * @version 0.1
 *
 */
class PasswordGenerator {
	private static $minLenght = 8;
	private static $maxLenght = 10;
	
	private static $vowelsArray = array('a', 'e', 'i', 'o', 'u', 'y');
	private static $consonantArray = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'z');
	
	private static $letterToDigit = array(	'z' => '3',
											'ch' => '4',
											'for' => '4',
											's' => '5');
	
	/**
	 * Генерирует легкозапоминаемый пароль
	 *
	 */
	static function  generatePassword(){
		mt_srand(microtime(true) * 1000000);
		$passwordLength = mt_rand(self::$minLenght, self::$maxLenght);
		$password = '';
		$previusLetterVowels = (bool) mt_rand(0, 1);
		//предварительная генерация
		for ($i = 0; $i < $passwordLength; $i++){
			if ($previusLetterVowels && mt_rand(0, 5)){
				$password .= self::$consonantArray[mt_rand(0, 19)];
				$previusLetterVowels = !$previusLetterVowels;
			} else {
				$password .= self::$vowelsArray[mt_rand(0, 5)];
				$previusLetterVowels = !$previusLetterVowels;
			}
		}
		
		//заменяем буквы на цифры, но не всегда
		foreach (self::$letterToDigit as $key => $value){
			if (substr_count($password, $key) && mt_rand(0, 2)){
				$password = str_replace($key, $value, $password);
			}
		}
		
		//добавляем цифры
		if ($passwordLength < self::$maxLenght && mt_rand(0, 2)){
			for ($i = $passwordLength; $i <= self::$maxLenght; $i++){
				if (mt_rand(0, 2)){
					$password .= mt_rand(1, 9);
				} else {
					$password = mt_rand(1, 9) . $password;
				}
				if (mt_rand(0, 1)){
					break;
				}
			}
		}
		
		return self::randomUC($password);
	}
	
	private static function randomUC($string){
		$len = strlen($string);
		for ($i = 0; $i < $len; $i++){
			if (!mt_rand(0, 4)){
				$string = substr_replace($string, strtoupper($string[$i]), $i, 1);
			}
		}
		return $string;
	}
}
?>