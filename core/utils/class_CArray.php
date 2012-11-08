<?php
	class CArray{
		
		/*
			Возвращает пересечение массивов без учета порядка следования элементов
		
		static function intersection($array1, $array2){
			$intersectionArray = array();
			foreach ($array1 as $key => $value){
				if (in_array($value, $array2)){
					$intersectionArray[] = $value;
				}
			}
		}*/
		
		
		/*Возвращает массив из элементов массива 1, которых нет в массиве 2*/
		static function different($array1, $array2){
			//Если массивы различны, то ищем те элементы, которых нет в массиве 2
			$different = array();
			if (!self::isEqual($array1, $array2)){
				//сортируем
				$array1 = self::sortAllKeys($array1);
				$array2 = self::sortAllKeys($array2);
				foreach ($array1 as $key => $value){
					if (!in_array($value, $array2)){
						$different[] = $value;
					}
				}
			}
			return $different;
		}
		
		//Проверяет равенство массивов, не учитывая порядок следования элементов
		static function isEqual($array1, $array2, &$resultString = ''){
			$isEqual = true;
			/*Проверяем, совпадает ли количество элементов в массиве
			Если да, то перебираем каждый элемент массива, если это не вложенный массив, то ищем его в другом,
			при этом проверяем, не совпадают ли у них ключи, если ключи - строки, или же забиваем на совпадение ключей, если они - числа*/
			//Сначала отсортируем оба массива => в этом случае исключаются траблы с ключами (т.е. если у двух подмассивов строковые ключи, то они будут отстортированы)
			$array1 = self::sortAllKeys($array1);
			$array2 = self::sortAllKeys($array2);
			if (count($array1) == count($array2)){
				foreach ($array1 as $key => $value){
					//Если найден такой элемент, то проверяем его ключ
					//строковые должны совпадать, цифровые - не обязательно
					$keyArray2 = array_search($value, $array2);
					if ($keyArray2 !== false){//используем эквивалентность, т.к. номер элемента м.б. = 0
						if ((!is_numeric($key) || !is_numeric($keyArray2)) && $key != $keyArray2){
							$resultString = 'не совпадают строковые значения ключей ' . $key . ' и ' . $keyArray2;
							$isEqual = false;
							break;
						}
					} else {
						$resultString = 'не найден элемент ';
						$isEqual = false;
						break;						
					}
				}
			} else {
				$resultString = 'количество элементов не совпадает';
				$isEqual = false;
			}
			return $isEqual;
		}
		
		//ф-ция сортировки всех ключей массива + вложенных подмассивов
		static function sortAllKeys($array){
			//Сортируем первоначальный массив
			ksort($array);
			reset($array);
			foreach ($array as $key => &$value){
				if (is_array($value)){
					$value = self::sortAllKeys($value);
				}
			}
			return $array;
		}
		
		
		/*Принимает количество элементов всего, количество элементов в странице и номер той страницы, которую надо отметить + номер первого элемента. Возвращает массив вида array('1' => array( 'checked' => true,
										'firstElement' => 0,
										'endElement' => 10,
										'countElements' => 11));
						где 1 - номер страницы (нумерация страниц начинается с единицы по умолчанию)
							firstElement - номер 1го элемента в диапазоне
							endElement - номер последнего элемента в диапазоне(по умолчанию, нумерация элементов с нуля)
							countElements - количество элементов на странице*/
		static function getPageArray($countAll, $countOnPage, $checkPage = 1, $firstElementNumber = 0, $firstPageNumber = 1){
			//формируем массив
			$array = array();
			$pages = ceil($countAll / $countOnPage); //число страниц
			//Последний элемент для итерации
			$end = $pages + $firstPageNumber;
			$endElementTemp = 0;
			for ($i = $firstPageNumber; $i < $end; $i++){
				$firstElementTemp = !$endElementTemp ? $firstElementNumber : $endElementTemp + 1;
				$endElementTemp = ($firstElementTemp + $countOnPage - 1) < $countAll ? ($firstElementTemp + $countOnPage - 1) : $countAll;
				$array[$i] = array( 'checked' => $i == $checkPage ? true : false,
									'firstElement' => $firstElementTemp,
									'endElement' => $endElementTemp,
									'countElements' => $endElementTemp - $firstElementTemp + 1);
			}
			return $array;
		}
		
		/*//возвращает максимальный элемент массива. Если задать ключ, среди которых надо искать, то ищет в массиве этот ключ в минимальном вложении
		static function arrayMax($array, $keyToFound = null, $tempMax = null){
			if (!$keyToFound){
				$firstIteration = true;
				foreach ($array as $value){
					if ($firstIteration){
						$max = $value;
					}
					if ($max < $value) {
						$max = $value;
					}
					$firstIteration = false;
				}
			} else {//ищем минимальную вложенность
				$temp = array();
				foreach ($array as $key => $value){
					
				}
			}
		}*/
		
		/*сортирует массив по выбранному ключу
		Например, передан массив вида 
		array([] => array(	'Name' => 'имя в БД',
							'URLName' => 'имя в URL',
							'Date' => 'дата создания',
							'ModifyDate' => 'дата изменения',
							'ComponentID' => 'componentID',
							'ComponentType' => 'тип'))
		Если надо отсортировать все элементы по одному из ключей (Name, URLName и т.д.)
		*/
		static function sort($array, $keyName, $desc = false, $saveKeys = false){
			if (is_array($array)){
				$minLevel = self::getMinimumNestedLevel($array, $keyName, 0);
				$results = array(); //массив результатов вида ключ - значение переменной
				foreach ($array as $key => $value){
					if (is_array($value)){
						$results[$key] = self::findArrayValue($value, $keyName, 1, $minLevel);
					}
				}
			}
			//Теперь сортируем результат
			$desc ? arsort($results) : asort($results);
			//Формируем выходной массив
			$arrayToReturn = array();
			foreach ($results as $key => $value){
				if ($saveKeys){
					$arrayToReturn[$key] = $array[$key];
				} else {
					$arrayToReturn[] = $array[$key];
				}
			}
			return $arrayToReturn;
		}
		
		//ищет на заданном уровне вложенности массива определённый ключ и возвращает его значение
		static function findArrayValue($array, $keyName, $currentLevel = 0, $maxLevel = 0){
			foreach ($array as $key => $value){
				if ($key === $keyName && $currentLevel === $maxLevel){
					return $value;
				}
				if (is_array($value) && $currentLevel < $maxLevel){
					$temp = self::findArrayValue($value, $keyName, $currentLevel + 1, $maxLevel);
					if ($temp !== false){
						return $temp;
					}
				}
			}
			return false;
		}
		
		//поиск минимального уровня вложенности
		static function getMinimumNestedLevel($array, $keyName, $currentLevel = 0){
			if (is_array($array)){
				$min = array();
				foreach ($array as $key => $value){
					if ($key === $keyName){
						return $currentLevel;
					}
					if (is_array($value)){
						$temp = self::getMinimumNestedLevel($value, $keyName, $currentLevel + 1);
						if ($temp !== false){
							$min[] = $temp;
						}
						//Если этот уровень только на 1 больше текущего, то меньше мы уже все равно не найдем - можем вернуть этот
						if ($temp === $currentLevel + 1){
							return $temp;
						}
					}
				}
				if (count($min)){
					return min($min);
				}
			}
			return false;
		}
		
		//Преобразование массива в строку вида array(....
		//не сериализация
		static function getStringFromArray($array){
			if (is_array($array)){
				$contentArray = array();
				foreach ($array as $key => $value){
					//чтобы лишний раз не делать рекурсивный вызов
					if (!is_array($value)){
						$contentArray[] = '\'' . addslashes($key) . '\' => \'' . addslashes($value) . '\'';
					} else {
						$contentArray[] = '\'' . addslashes($key) . '\' => ' . self::getStringFromArray($value);
					}
				}
				$content = 'array(' . join(', ', $contentArray) . ')';
			} else {
				$content = $array;
			}
			return $content;
		}
		
		/**
		 * Удаляет элемент массива с заданным значением, возвращает изменённый массив
		 *
		 * @param array $arr
		 * @param mixed $value
		 * 
		 * @return array
		 */
		static function removeFromArray($arr, $value){
			$temp = array();
			foreach ($arr as $val){
				if ($val != $value){
					$temp[] = $val;
				}
			}
			return $temp;
		}
	}
?>