<?php
/* *����� ��� ��������� ����
*/
class CDate
{
	static  $MONTH_NAMES_RUS = array("������", "�������", "�����", "������", "���", "����", "����", "�������", "��������", "�������", "������", "�������"),
		 $MONTH_NAMES_RUS2 = array("������", "�������", "����", "������", "���", "����", "����", "������", "��������", "�������", "������", "�������");
	
	//������������� ������ ���� ��� ����, ����, ������ ������������ ���� ������� 0 - $MONTH_NAMES_RUS ��� 1 - $MONTH_NAMES_RUS2
	public	$dayFieldSize = 3,
			$yearFieldSize = 5,
			$hourFieldSize = 3,
			$minuteFieldSize = 3,
			$secundFieldSize = 3,
			$defaultMonthArray = 1,
	
	//������������� ��������� ���������� ��� ��� ������ � ��������� ����
	//�.�. localtime ���������� ���������� ���, ������� � 1900, �� � ����������� ���-�� ���������
			 $yearStart = 1900,
	
	//��� ���������� ���� - ��� ��������������� ��� ����������� ����
	//� ��������, ��� ����� � ������������� ���� ��� function setDateSet, �� ��� �� ��� ������, ��� ����� UnixTimeStamp
		 	$day,
			$month,
			$year,
			$hours,
			$minutes,
			$secunds,
			
			$timestamp;
	
	//������������� ����� �� �������� � ��������� ����� ��� ����������� ������
	//timestamp - ����� � ������� UNIX_TIME_STAMP, �� ��������� ���������� ������� �����
	function setDateSet($timestamp = '', $dateFormat = 'd M Y  h : i : s', $namePrefix = ''){
		$controls = new Controls();
		//��������� ������ ������������, �.�. �.�. ������� ������ 0
		if ($timestamp === ''){
			$timestamp = date('U');
		}
		
		$dateArray = localtime($timestamp, true);
		
		//���� ���� �������� ������ ������, �� ������������� ����� ������, �.�. ������ �.�. ���� �������� ������ �������, � ������� ������ ����
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
	
	//�������� timestamp �� ��������� ������ ����
	//� �������� ���������� �������� ������������ ���������� ����
	function getTimeStampDate($namePrefix = ''){
		$this->year = CParams::loadIntParam($namePrefix . 'Year', $this->year);
		//���� �� ���������� ���� ���������� ���, �� ���������� ����������� ���� ������������ - ���������� 0
		if ($this->year){
			$this->month = CParams::loadIntParam($namePrefix . 'Month', $this->month);
			$this->day = CParams::loadIntParam($namePrefix . 'Day', $this->day);
			$this->hours = CParams::loadIntParam($namePrefix . 'Hours', $this->hours);
			$this->minutes = CParams::loadIntParam($namePrefix . 'Minutes', $this->minutes);
			$this->secunds = CParams::loadIntParam($namePrefix . 'Secunds', $this->secunds);
			//��� mktime ��������� ������� ���������� � 1
			$this->timestamp = mktime($this->hours, $this->minutes, $this->secunds, $this->month + 1, $this->day, $this->year);
		} else {
			$this->timestamp = 0;
		}
		return $this->timestamp;
	}
	
	//���������� ����/�����, ������� ����� MR1 �� ����� �� $MONTH_NAMES_RUS = array("������", "�������", ...)
	//MR2 �� $MONTH_NAMES_RUS2 = array("������", "�������",
	static function getRusDate($unixTimeStamp, $dateFormat = 'd MR1 y | H:i:s'){
		//�������� ����� ������ � �������
		$index = date('m', $unixTimeStamp) - 1;
		//�������� MR1 � MR2
		$dateFormat = str_replace('MR1', self::$MONTH_NAMES_RUS[$index], $dateFormat);
		$date = str_replace('MR1', self::$MONTH_NAMES_RUS[$index], $dateFormat);
		return date($dateFormat, $unixTimeStamp);
	}
}
?>