<?php
/**
 * Класс предназначен для работы с картинками
 * @author Vladimir Kuptcov
 * @version 1.5
 */
class Image{
	/*
	typeOfResize - тип ресайза
		0 - не изменять
		1 - масштабировать по ширине
		2 - масштабировать по высоте
		3 - масштабировать по ширине и высоте
		4 - жестко подгонять под указанный размер
	
	$resizeSmall - увеличивать маленькую картинку
	$needFormat  - можно указать формат выходного файла
	*/
	
	/**
	 * Функция ресайза картинок
	 *
	 * @param string $src ссылка на картинку-источник
	 * @param string $dest имя файла, в который происходит вывод. Если не указано - вывод в браузер
	 * @param integer $width ширина выходного изображенияs
	 * @param integer $height высота выходного изображенияs
	 * @param integer $typeOfResize способ ресайза.
	 *	0 - не изменять
	 *	1 - масштабировать по ширине
	 *	2 - масштабировать по высоте
	 *	3 - масштабировать по ширине и высоте
	 *	4 - жестко подгонять под указанный размер
	 * @param string $needFormat формат картинки, по умолчанию тот же, что и у файла источника
	 * @param integer $quality качество картинки от 0 до 100
	 * @param boolean $resizeSmall увеличивать ли картинку, которая меньше заданных 
	 * @param string $errorString строка, в которую записывается сообщение об ошибке
	 * @return boolean true в случае удачи, false в случае ошибки
	 */
	static function imgResize($src, $dest = null, $width, $height, $typeOfResize = 3, $needFormat = null, $quality = 100, $resizeSmall = false, &$errorString = ''){
		
		if (!file_exists($src)){
			$errorString = 'Файл не существует';
			return false;
		}

			$size = getimagesize($src);

			if ($size === false){
				$errorString = 'Недопустимый формат файла';
				return false;
			}

			$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));

			$icfunc = "imagecreatefrom" . $format;
			if (!function_exists($icfunc)){
				$errorString = 'Не определена функция ' . $icfunc;
				return false;
			}
		
			//Определяем масштаб
			switch ($typeOfResize){
			case 0:
				$ratio = 1;
				break;
		
	  	case 1:
				$ratio = $width / $size[0];
				break;
		
			case 2:
	  			$ratio = $height / $size[1];
				break;
		
			case 3:
				$ratio = self::getScale($size[0], $size[1], $width, $height);
				break;
			case 4:
				$ratio = 1;
				break;
			}
			
			//Если фотка маленькая и её не разрешено ресайзить, то устанавливаем масштаб 1
			if ($ratio > 1 && !$resizeSmall){
				$ratio = 1;
			}

			if ($typeOfResize != 4){
				$new_width  = $size[0] * $ratio;
				$new_height  = $size[1] * $ratio;
			} else {
				$new_width = $width;
				$new_height = $height;
			}

			$isrc = $icfunc($src);
			$idest = imagecreatetruecolor($new_width, $new_height);

		imagecopyresampled($idest, $isrc, 0,0, 0, 0, $new_width, $new_height, $size[0], $size[1]);

		//Устанавливаем формат вывода
		$needFormat = $needFormat ? $needFormat : $format;
		$distFunc = 'image' . $needFormat;
		if (!function_exists($distFunc)){
			$errorString = 'Функция ' . $distFunc . ' недоступна.';
			return false;
		}
		
		//Если не указан выходной файл - выводим в браузер
		if (!$dest){
			header('Content-type: image/' . $needFormat);
		}
			$distFunc($idest, $dest, $quality);

			imagedestroy($isrc);
			imagedestroy($idest);


			return true;
	}
	
	private function getScale($width, $height, $newWidth, $newHeight){
		$xRatio = $newWidth / $width;
		$yRatio = $newHeight / $height;
		return min($xRatio, $yRatio);
	}
	
	/**
	 * Определение является ли файл файлом gif, png или jpg
	 *
	 * @param string $file ссылка на файл
	 * @param string $errorString строка с сообщением об ошибке
	 * @return boolean
	 */
	function isViewAbleFile($file, &$errorString = ''){
		$allowTypes = array(1, 2, 3);
		if (!file_exists($file)){
			$errorString = 'Файл не существует';
			return false;
		}
		$type = getimagesize($file);
		if (!in_array($type[2], $allowTypes)) return false;
		return true;
	}
}
?>