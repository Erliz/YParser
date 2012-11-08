<?php
/* *Класс для обработки даты
*/
class CDate
{
	static  $MONTH_NAMES_RUS = array("января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"),
		 $MONTH_NAMES_RUS2 = array("январь", "февраль", "март", "апрель", "май", "июнь", "июль", "август", "сентябрь", "октябрь", "ноябрь", "декабрь");
	
	//Устанавливаем размер поля для даты, года, массив используемых имен месяцев 0 - $MONTH_NAMES_RUS или 1 - $MONTH_NAMES_RUS2
	public	$dayFieldSize = 3,
			$yearFieldSize = 5,
			$hourFieldSize = 3,
			$minuteFieldSize = 3,
			$secundFieldSize = 3,
			$defaultMonthArray = 1,
	
	//Устанавливаем насколько изменяется год для показа в текстовом поле
	//т.к. localtime возвращает количество лет, начиная с 1900, то к полученному рез-ту добавляем
			 $yearStart = 1900,
	
	//Это компоненты даты - они устанавливаются при определении даты
	//в принципе, ими можно и устанавливать дату для function setDateSet, но это не так удобно, как через UnixTimeStamp
		 	$day,
			$month,
			$year,
			$hours,
			$minutes,
			$secunds,
			
			$timestamp;
	
	//Устанавливает набор из селектов и текстовых полей для последующей выдачи
	//timestamp - время в формате UNIX_TIME_STAMP, по умолчанию становится текущей датой
	function setDateSet($timestamp = '', $dateFormat = 'd M Y  h : i : s', $namePrefix = ''){
		$controls = new Controls();
		//Проверяем точное соответствие, т.к. м.б. передан именно 0
		if ($timestamp === ''){
			$timestamp = date('U');
		}
		
		$dateArray = localtime($timestamp, true);
		
		//Если была передана пустая строка, то устанавливаем такой формаь, т.к. вполне м.б. влом задавать строку формата, а префикс задать надо
		if (!$dateFormat) {
			$dateFormat = 'd M Y  h : i : s';
		}
		
		$content = '';
		for ($i = 0; $i < strlen($dateFormat); $i++){
			switch ($dateFormat[$i]){
				case 'd':
					if (!$i || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeTextBox($namePrefix . 'Day', str_pad($dateArray['tm_mday'], 2, 0, STR_PAD_LEFT), $this->dayFieldSize, 2);
					}
					break;
					
				case 'M':
					if (!$i || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeSelect($namePrefix . 'Month', $this->defaultMonthArray ? self::$MONTH_NAMES_RUS2 : self::$MONTH_NAMES_RUS, $dateArray['tm_mon']);
					}
					break;
				
				case 'Y':
					if (!$i || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeTextBox($namePrefix . 'Year', $this->yearStart + $dateArray['tm_year'], $this->yearFieldSize, 4);
					}
					break;
					
				case 'h':
					if (!$i || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeTextBox($namePrefix . 'Hours', str_pad($dateArray['tm_hour'], 2, 0, STR_PAD_LEFT), $this->hourFieldSize, 2);
					}
					break;
					
				case 'i':
					if (!$i || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeTextBox($namePrefix . 'Minutes', str_pad($dateArray['tm_min'], 2, 0, STR_PAD_LEFT), $this->minuteFieldSize, 2);
					}
					break;
					
				case 's':
					if ($i = 0 || $dateFormat[$i - 1] != '\\'){
						$content .= $controls->writeTextBox($namePrefix . 'Secunds', str_pad($dateArray['tm_sec'], 2, 0, STR_PAD_LEFT), $this->secundFieldSize, 2);
					}
					break;
				
				default:
					$content .= $dateFormat[$i];
					break;
			}
		}
		
		return $content;
	}
	
	//Получает timestamp из заданного набора даты
	//в качестве дефолтовых значений используются компоненты даты
	function getTimeStampDate($namePrefix = ''){
		$this->year = CParams::loadIntParam($namePrefix . 'Year', $this->year);
		//Если не установлен даже дефолтовый год, то продолжать определение даты бесмыссленно - возвращаем 0
		if ($this->year){
			$this->month = CParams::loadIntParam($namePrefix . 'Month', $this->month);
			$this->day = CParams::loadIntParam($namePrefix . 'Day', $this->day);
			$this->hours = CParams::loadIntParam($namePrefix . 'Hours', $this->hours);
			$this->minutes = CParams::loadIntParam($namePrefix . 'Minutes', $this->minutes);
			$this->secunds = CParams::loadIntParam($namePrefix . 'Secunds', $this->secunds);
			//для mktime нумерация месяцев начинается с 1
			$this->timestamp = mktime($this->hours, $this->minutes, $this->secunds, $this->month + 1, $this->day, $this->year);
		} else {
			$this->timestamp = 0;
		}
		return $this->timestamp;
	}
	
	//Возвращает дату/время, заменяя слова MR1 на слова из $MONTH_NAMES_RUS = array("января", "февраля", ...)
	//MR2 на $MONTH_NAMES_RUS2 = array("январь", "февраль",
	static function getRusDate($unixTimeStamp, $dateFormat = 'd MR1 y | H:i:s'){
		//Получаем номер месяца в массиве
		$index = date('m', $unixTimeStamp) - 1;
		//Заменяем MR1 и MR2
		$dateFormat = str_replace('MR1', self::$MONTH_NAMES_RUS[$index], $dateFormat);
		$date = str_replace('MR1', self::$MONTH_NAMES_RUS[$index], $dateFormat);
		return date($dateFormat, $unixTimeStamp);
	}
}
?>