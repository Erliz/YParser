<?php
/**
 * Класс отвечающий за Демона парсера
 * Назначает задания, запускает и поддерживает работу потоков
 */
class ParserDaemon {
	// Текущее имя хоста
	protected $host;
	// Количество потоков
	protected $numbers=20;
	// ID парсера из log_parse
	protected $parse_id;
	// Флаг на использование modelid маркета из базы
	protected $b_title=false;
	// Список id потомков
	protected $childs_id=array();
	// Вообще это локальная переменная для проверки списка заданий
	protected $task_id=array();
	// Параметры запуска под процессов (Раньше использовался для передачи пароль для ставок)
	protected $url;
	
	/**
	 * Контроллер отвечающий за порядок последовательных действий/запусков методов
	 * 
	 * @param int $parse_id ID парса
	 * @param string $pass пароль для ставок
	 */
	function __construct($parse_id, $pass=null){
		$this->parse_id=$parse_id;
		// Вытаскивание данных о текущем парсе
		$data=Registry::get('db')->selectRow("SELECT `type`,`file`,`proxy` FROM `log_parse` WHERE `id`=? LIMIT 1",$this->parse_id);
		// указание пароля для ставок
		$this->url=isset($pass)?"&pass=".$pass:"";
		// парс файла с заданием
		$list=$this->file_parse($data['file']);
		// Отметка о старте демона, указание его pid и предпологаемое количество строк заданий
		Registry::get('db')->query("UPDATE `log_parse` SET `pid`=?, `rows_plan`=?, `time_start`=? WHERE `id`=?",getmypid(),sizeof($list)-1,time(),$this->parse_id);
		// Если количество заданий меньше количества поток, используется только 1 поток.
		if (sizeof($list)<=$this->numbers) $this->numbers=1;
		// настройки для использования динамических прокси
		if($data['proxy']=='dynamic'){
			$this->numbers=30;
			// Заливка списка прокси во временную таблицу
			IpDynamic::set_ip_table();
		}
		// Выбор метода для парса и назначение заданий
		switch ($data['type']) {
			case 'price': $this->task_set_price($list);break;
			case 'rate': $this->task_set_rate($list);break;
			case 'stat': echo "temporary unavalible!";exit;
			default: echo "no such parse type!";exit;
		}
		unset($list);
		// Инииализация хоста
		if(!isset($_SERVER['HTTP_HOST']))$this->host="webtools.ru:80";
		else $this->host=$_SERVER['HTTP_HOST'];
		// Запуск дочерних процессов
		$this->start_parsers();
		// Запуск основной работы демона - наблюдение за потоками
		$result=$this->watching();
		if ($result==true)echo 'Парсер успешно закончил работу';
		elseif ($result==false)echo 'Парсер !@#$%^&* завершил скрипт!!!';
		// Очистка временной таблицы с прокси
		if($data['proxy']=='dynamic') IpDynamic::delete_table();
	}
	
	/**
	 * Парсинг файла с заданиями
	 * 
	 * @param string $file путь к файлу
	 * @return array список заданий 
	 */
	private function file_parse($file){
		$file = fopen ($file,"r");
		$rows=0;
		while ($list[$rows] = fgetcsv ($file, 1000, ";")) $rows++;
		return $list;
	}
	
	/**
	 * Авторизация в яндекс партнёрке
	 * Планировалось, что демон будет создавать уже авторизованные куки, которые будут подцелять потоки.
	 * 
	 * @param int $user_id id пользователя
	 * 
	 * @return bool
	 */
	function yandex_auth($user_id){
		if (file_exists("cookies/{$user_id}_rate.txt")) unlink("cookies/{$user_id}_rate.txt");		
		$login=Registry::get('db')->selectCell("SELECT `login` FROM `shops` WHERE `id`=(SELECT `shop_id` FROM `shops_id` WHERE `id`=?) LIMIT 1",Registry::get('region_id'));
		$proxy = new PageFinder(Registry::get('proxy'),Registry::get('region_id'));
		$check=$proxy->getpage("http://passport.yandex.ru/passport?mode=auth",false);
		if($res==false) {echo "PageFinder return (false)!<br/>"; return false;}
		if (strpos($check, $login)){
			$proxy->curlclose();
			return true;
		}
		else {
			preg_match_all('/<input type="hidden" name="idkey" value="(.*?)" \/>.*/', $check, $pm);
			$proxy->curl_post('login='.urlencode($login).'&passwd='.$pass.'&idkey='.$pm[0][0].'&retpath='.urlencode('http://partner.market.yandex.ru/').'&twoweeks=&In='.urlencode('Войти').'&timestamp='.time()."120");
			$check=$proxy->getpage('https://passport.yandex.ru/passport?mode=auth',false,'https://passport.yandex.ru/passport?mode=auth&retpath='.urlencode('http://partner.market.yandex.ru/'),true);
			if($res==false) {echo "PageFinder return (false)!<br/>"; return false;}
			if (strpos($check, $login)){
				$proxy->curlclose();
				return true;
			}
			else return false;
		}
	}
	
	/**
	 * Расстановка задачи между потоками для цен
	 * 
	 * @param array $list список с заданиями
	 * 
	 * @return bool
	 */
	protected function task_set_price($list){
		// Количество заданий на поток
		$count=ceil(sizeof($list)/$this->numbers);
		for ($i=0;$i<$this->numbers;$i++){
			// Инициализация потока в базе
			$this->childs_id[$i]=Registry::get('db')->query("INSERT INTO `log_pid` (`parse_id`) VALUES (?)",$this->parse_id);			
			for($y=0;$y<$count;$y++){
				$num=$y+($count*$i);
				// Проверка на валидность задания
				if(isset($list[$num][0]) && $list[$num][0]!=0 && count($list[$num]>1)){
					// Форматирование данных из файла
					$MPI=isset($list[$num][2])? round(str_replace(",", ".", str_replace(" ","",$list[$num][2])),2) : 0;
					$mvic=isset($list[$num][3])? round(str_replace(",", ".", str_replace(" ","",$list[$num][3])),2) : 0;
					$moic=isset($list[$num][4])? round(str_replace(",", ".", str_replace(" ","",$list[$num][4])),2) : 0;
					// Добавление задания в базу
					$this->task_id[$i][]=Registry::get('db')->query("INSERT INTO `queryPrice` (`parse_id`,`pid_id`,`tovar_id`,`title`,`mvic`,`moic`,`MPI`) VALUES (?,?,?,?,?,?,?)",$this->parse_id,$this->childs_id[$i],trim((int)$list[$num][0]),trim($list[$num][1]),$mvic,$moic,$MPI);
				}
				elseif (isset($list[$num][0])) echo $list[$num][0].": Недостаточно колонок для парсинга! <br/>";
			}
			// Обновление информации о потоке, добавление количества заданий
			if(isset($this->childs_id[$i])) Registry::get('db')->query("UPDATE `log_pid` SET `rows_plan`=? WHERE `id`=?",count(@$this->task_id[$i]),$this->childs_id[$i]);
		}
		// Проверка на полное распределение заданий на процессы
		if (count($this->task_id)==count($list)) return true;
		else return false;
	}

	/**
	 * Распределение задачи между потоками для ставок
	 * 
	 * @param array $list список с заданиями
	 * 
	 * @return bool
	 */
	protected function task_set_rate($list){
		$count=ceil(sizeof($list)/$this->numbers);
		for ($i=0;$i<$this->numbers;$i++){
			$this->childs_id[$i]=Registry::get('db')->query("INSERT INTO `log_pid` (`parse_id`) VALUES (?)",$this->parse_id);
			for($y=0;$y<$count;$y++){
				$num=$y+($count*$i);
				if(isset($list[$num][0])) $this->task_id[$i][]=Registry::get('db')->query("INSERT INTO `queryData` (`parse_id`,`pid_id`,`query_id`,`title`) VALUES (?,?,?,?)",$this->parse_id,$this->childs_id[$i],trim($list[$num][0]),trim($list[$num][1]));
			}
			if(isset($this->childs_id[$i])) Registry::get('db')->query("UPDATE `log_pid` SET `rows_plan`=? , `rows_done`=0 WHERE `id`=? LIMIT 1",count($this->task_id[$i]),$this->childs_id[$i]);
		}
		if (count($this->task_id)==count($list)) return true;
		else return false;
	}
	
	/**
	 * Запуск всех потоков
	 * 
	 * @return bool
	 */
	private function start_parsers(){
		foreach ($this->childs_id as $value) {
			$this->start_parsers_one($value);
			sleep(2);
		}
		if(Registry::get('db')->selectCell("SELECT COUNT(*) FROM `log_pid` WHERE `parse_id`=?",$this->parse_id)==$this->numbers) return true;
		else return false;
	}
	
	/**
	 * Запуск одного потока с указанным id
	 * 
	 * @param int $id ID потока
	 * 
	 * @return int PID запущенного потока
	 */
	private function start_parsers_one($id){
		echo "start_parsers_one:".$id.'<br/>';
		$header= "GET ".PARS_URL."parser.php?pid_id={$id}".@$this->url." HTTP/1.0\r\n";
		$header.= "Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/vnd.ms-excel, application/msword, */*\r\n";
		$header.= "Accept-Language: ru\r\n";
		$header.= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header.= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; (R1 1.5))\r\n";
		$header.= "Host: ".$this->host."\r\n\r\n";
		$sckt = fsockopen($this->host, 80);
		fputs($sckt,$header);
		sleep(1);
		fclose($sckt);
		$pid=Registry::get('db')->selectCell("SELECT `pid` FROM `log_pid` WHERE `id`=?",$id);
		return $pid;
	}
	
	/**
	 * Процесс контроля и остановки запущенных потоков
	 * 
	 * @return bool
	 */
	private function watching(){
		$pid=Array();
		$count=Array();
		$done=false;
		// цикл бечконечности -_-
		while(1){
			$num=0;
			// Проверка на принудительную остановку парсера
			if(Registry::get('db')->selectCell("SELECT `time_stop` FROM `log_parse` WHERE `id`=?",$this->parse_id)==='0'){
				// Остановка всех дочерних потоков
				Registry::get('db')->query("UPDATE `log_pid` SET `time_stop`=0 WHERE `parse_id`=? AND `time_stop` IS NULL",$this->parse_id);
				break;
			}
			$pid_list=array();
			// проверка на существование процесса для потока (Они имеют свойства отваливаться)
			foreach($this->childs_id as $value){
				if (isset($pid[$value])) continue;
				unset($ans,$data);
				// Выборка данных потока
				$data=Registry::get('db')->selectRow("SELECT `pid`,`time_stop`,`rows_done`,`aborted` FROM `log_pid` WHERE `id`=?",$value);
				// Сравнение, все ли задания пройдены
				$count[$value]=$data['rows_done'];
				// Обработка аварийной остановки
				if ($data['time_stop']>0 or $data['aborted']=='1'){
					$pid[$value]=true;
					continue;
				}
				// Выборка информации из консоли сервера
				exec('ps -aux | grep " '.$data['pid'].' " | grep -v grep',$ans,$code);
				// Если процесс убит, то запуск потока ещё раз
				if(count($ans)==0 && $code==1) $data['pid']=$this->start_parsers_one($value);
				if($data['pid']!=null) $pid_list[]=$data['pid'];
			}
			if(count($pid_list)>0){
				// Подсчёт процессов
				$pid_list=array_count_values($pid_list);
				// Если попали в базу процессы с 1 PID, то его уничтожаем в консоле, так как какойто из них точно умер, а какой не узнать
				foreach($pid_list as $key=>$value) if ($value>1) exec('kill '.$key);
			}
			// Проверка на завершение всех потоков
			if(count($pid)==count($this->childs_id)){
				// Остановка и обновление данных парсера
				Registry::get('db')->query("UPDATE `log_parse` SET `rows_done`=?, `time_stop`=? WHERE `id`=?",array_sum($count),time(),$this->parse_id);
				$done=true;
				break;
			}
			// обновление данных о текущем парсе (кол-во пройденных строк)
			else Registry::get('db')->query("UPDATE `log_parse` SET `rows_done`=? WHERE `id`=?",array_sum($count),$this->parse_id);
			sleep(10);
		}
		if ($done==true)return true;
		else return false;
	}
}
?>