<?php
	class CArray{
		
		/*
			���������� ����������� �������� ��� ����� ������� ���������� ���������
		
		static function intersection($array1, $array2){
			$intersectionArray = array();
			foreach ($array1 as $key => $value){
				if (in_array($value, $array2)){
					$intersectionArray[] = $value;
				}
			}
		}*/
		
		
		/*���������� ������ �� ��������� ������� 1, ������� ��� � ������� 2*/
		static function different($array1, $array2){
			//���� ������� ��������, �� ���� �� ��������, ������� ��� � ������� 2
			$different = array();
			if (!self::isEqual($array1, $array2)){
				//���������
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
		
		//��������� ��������� ��������, �� �������� ������� ���������� ���������
		static function isEqual($array1, $array2, &$resultString = ''){
			$isEqual = true;
			/*���������, ��������� �� ���������� ��������� � �������
			���� ��, �� ���������� ������ ������� �������, ���� ��� �� ��������� ������, �� ���� ��� � ������,
			��� ���� ���������, �� ��������� �� � ��� �����, ���� ����� - ������, ��� �� �������� �� ���������� ������, ���� ��� - �����*/
			//������� ����������� ��� ������� => � ���� ������ ����������� ������ � ������� (�.�. ���� � ���� ����������� ��������� �����, �� ��� ����� ��������������)
			$array1 = self::sortAllKeys($array1);
			$array2 = self::sortAllKeys($array2);
			if (count($array1) == count($array2)){
				foreach ($array1 as $key => $value){
					//���� ������ ����� �������, �� ��������� ��� ����
					//��������� ������ ���������, �������� - �� �����������
					$keyArray2 = array_search($value, $array2);
					if ($keyArray2 !== false){//���������� ���������������, �.�. ����� �������� �.�. = 0
						if ((!is_numeric($key) || !is_numeric($keyArray2)) && $key != $keyArray2){
							$resultString = '�� ��������� ��������� �������� ������ ' . $key . ' � ' . $keyArray2;
							$isEqual = false;
							break;
						}
					} else {
						$resultString = '�� ������ ������� ';
						$isEqual = false;
						break;						
					}
				}
			} else {
				$resultString = '���������� ��������� �� ���������';
				$isEqual = false;
			}
			return $isEqual;
		}
		
		//�-��� ���������� ���� ������ ������� + ��������� �����������
		static function sortAllKeys($array){
			//��������� �������������� ������
			ksort($array);
			reset($array);
			foreach ($array as $key => &$value){
				if (is_array($value)){
					$value = self::sortAllKeys($value);
				}
			}
			return $array;
		}
		
		
		/*��������� ���������� ��������� �����, ���������� ��������� � �������� � ����� ��� ��������, ������� ���� �������� + ����� ������� ��������. ���������� ������ ���� array('1' => array( 'checked' => true,
										'firstElement' => 0,
										'endElement' => 10,
										'countElements' => 11));
						��� 1 - ����� �������� (��������� ������� ���������� � ������� �� ���������)
							firstElement - ����� 1�� �������� � ���������
							endElement - ����� ���������� �������� � ���������(�� ���������, ��������� ��������� � ����)
							countElements - ���������� ��������� �� ��������*/
		static function getPageArray($countAll, $countOnPage, $checkPage = 1, $firstElementNumber = 0, $firstPageNumber = 1){
			//��������� ������
			$array = array();
			$pages = ceil($countAll / $countOnPage); //����� �������
			//��������� ������� ��� ��������
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
		
		/*//���������� ������������ ������� �������. ���� ������ ����, ����� ������� ���� ������, �� ���� � ������� ���� ���� � ����������� ��������
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
			} else {//���� ����������� �����������
				$temp = array();
				foreach ($array as $key => $value){
					
				}
			}
		}*/
		
		/*��������� ������ �� ���������� �����
		��������, ������� ������ ���� 
		array([] => array(	'Name' => '��� � ��',
							'URLName' => '��� � URL',
							'Date' => '���� ��������',
							'ModifyDate' => '���� ���������',
							'ComponentID' => 'componentID',
							'ComponentType' => '���'))
		���� ���� ������������� ��� �������� �� ������ �� ������ (Name, URLName � �.�.)
		*/
		static function sort($array, $keyName, $desc = false, $saveKeys = false){
			if (is_array($array)){
				$minLevel = self::getMinimumNestedLevel($array, $keyName, 0);
				$results = array(); //������ ����������� ���� ���� - �������� ����������
				foreach ($array as $key => $value){
					if (is_array($value)){
						$results[$key] = self::findArrayValue($value, $keyName, 1, $minLevel);
					}
				}
			}
			//������ ��������� ���������
			$desc ? arsort($results) : asort($results);
			//��������� �������� ������
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
		
		//���� �� �������� ������ ����������� ������� ����������� ���� � ���������� ��� ��������
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
		
		//����� ������������ ������ �����������
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
						//���� ���� ������� ������ �� 1 ������ ��������, �� ������ �� ��� ��� ����� �� ������ - ����� ������� ����
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
		
		//�������������� ������� � ������ ���� array(....
		//�� ������������
		static function getStringFromArray($array){
			if (is_array($array)){
				$contentArray = array();
				foreach ($array as $key => $value){
					//����� ������ ��� �� ������ ����������� �����
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
		 * ������� ������� ������� � �������� ���������, ���������� ��������� ������
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