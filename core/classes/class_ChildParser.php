<?php
/**
 * Класс отвечающий за дочернии потоки парсера. 
 */
class ChildParser{
	// id дочернего процесса
	private $id=0;
	// id материнского процесса 
	private $parse_id=0;
	// id региона 
	private $region_id;
	// id юзера 
	private $user_id;
	// тип парсера - price или rate
	private $type;
	// флаг использования базы с кодами 
	private $base;
	// пароль для ставок 
	private $pass;
	// handle для прокси
	private $proxy;
	// тип прокси 
	private $proxy_type;
	// id площадки 
	private $platform;
	
	/**
	 * Инициализация переменных
	 * Добавление в базу отметки о старте потока 
	 * 
	 * @param int $pid_id ID демона
	 * @param string $type тип парса
	 */
	function __construct($pid_id, $type = null) {
		// Инициализация переменной базы 
		$this->db=$this->db();
		// константы
		Face::constan();
		// присвоение id 
		$this->id=(int)$pid_id;
		// Заносит в базу время старта и id процесса 
		$this->db->query("UPDATE `log_pid` SET `time_start`=?, `pid`=?, `time_stop`=NULL, `aborted`=0 WHERE `id`=?",time(),getmypid(),$this->id);
		// вытаскивание из базы данных по парсу 
		$sessions=$this->db->selectRow("SELECT `id`,`user_id`,`platform_id`,`region_id`,`type`,`proxy`,`b_title` FROM `log_parse` WHERE `id`=(SELECT `parse_id` FROM `log_pid` WHERE `id`=?) LIMIT 1",$pid_id);
		// Инициализация классовых переменных 
		#new school
		$this->parse_id=$sessions['id'];
		$this->proxy_type=$sessions['proxy'];
		$this->region_id=$sessions['region_id'];
		$this->user_id=$sessions['user_id'];
		$this->base=$sessions['b_title'];
		$this->platform=$sessions['platform_id'];
		$this->type=$sessions['type'];
		#end
		#OLD school
		Registry::set('user_id', $sessions['user_id']);
		Registry::set('region_id', $sessions['region_id']);
		Registry::set('platform_id', $sessions['platform_id']);
		Registry::set('proxy', $sessions['proxy']);
		Registry::set('parse_id', $sessions['id']);
		Registry::set('pid',$this->id);
		#end		
		if(isset($_GET['debug'])) echo "type choose: ".$this->type.'<br/>';
		// выбор метода для парсера
		switch ($this->type) {
			case 'rate': $this->rateParse();break;
			case 'price': $this->priceParse();break;
			case 'stat': $this->statParse();break;
			default:
				echo "no type selected!";
				exit;
		}
		// проставление времени остановки парсера
		$this->db->query("UPDATE `log_pid` SET `time_stop`=? WHERE `id`=?",time(),$this->id);
	}
	
	/**
	 *  Инициализация базы
	 */
	private function db(){
		Registry::set('db',Simple::createConnection());
		Registry::get('db')->setErrorHandler('Error::db_error');
		return Registry::get('db');
	}
	
	/**
	 * Метод для парса ставок
	 * Использует класс Parser для парса
	 */
	private function rateParse(){
		// Данные для авторизации на яндексе 
		$auth=$this->db->selectRow("SELECT `login`,`passwd`,`shops_id` FROM `platform_acc` as `pa` LEFt JOIN `platform_shops` as `ps` ON `ps`.`platform_acc_id`=`pa`.`id` WHERE `pa`.`platform_id`=? AND `ps`.`shop_id`=? LIMIT 1",$this->platform,$this->user_id);
		// Инициализация переменной поиска страницы 
		$this->proxy = new PageFinder($this->proxy_type,$this->region_id,$auth['login']);
		// проверка уже выполненного входа
		$answer = $this->proxy->getpage("http://passport.yandex.ru/passport?mode=auth",false);
		// если странице нет надписи нужного логина 
		if(!stripos($answer,$auth['login'])){
			// получение скрытого idkey
			preg_match('/<input type="hidden" name="idkey" value="(.*?)" \/>.*/', $answer , $pm);
			// подготовка post запроса 
			$this->proxy->curl_post('login='.urlencode($auth['login']).'&passwd='.$auth['passwd'].'&idkey='.$pm[1].'&retpath='.urlencode('http://partner.market.yandex.ru/').'&twoweeks=&In='.urlencode('Войти').'&timestamp='.time()."120");
			// отправка данных для авторизации 
			$this->proxy->getpage('https://passport.yandex.ru/passport?mode=auth',false,'https://passport.yandex.ru/passport?mode=auth&retpath='.urlencode('http://partner.market.yandex.ru/'),true);
			// очистка post запроса 
			$this->proxy->curl_post(0);
			// получение страницы личного кабинета 
			$res = $this->proxy->getpage("http://passport.yandex.ru/passport?mode=auth",false);
			if(!stripos($res,$auth['login'])) {
				// остановка потока из-за не пройденной авторизации 
				$this->db->query("UPDATE `log_pid` SET `time_stop`=?, `aborted`=1 WHERE `id`=?",time(),$this->id);
				if(isset($_GET['debug']) && $_GET['debug']==true)  echo $this->id.": Ошибка при авторизации.";
				else $this->db->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",$this->parse_id,time(),$this->id,'not authorize','auth_error');
				exit;
			}
			else{
				// закрытие PageFinder для сохранения куки 
				$this->proxy->curlclose();
				$this->proxy = new PageFinder($this->proxy_type,$this->region_id,$auth['login']);
			}
		}
		// обработка задания 
		while(1) {
			$rows_done=0;
			// проверка на отсутствие принудительной остановки 
			if($this->db->selectCell("SELECT `time_stop` FROM `log_pid` WHERE `id`=?",$this->id)==='0')break;
			// проверка на наличие задания 
			if($this->db->selectCell("SELECT SQL_NO_CACHE COUNT(*) FROM `queryData` WHERE `pid_id`=?",$this->id)==='0')break;
			// количество пройденных заданий 
			$rows_done = $this->db->selectCell("SELECT SQL_NO_CACHE `rows_done` FROM `log_pid` WHERE `id`=?",$this->id);
			// получение следующего задантя
			$data = $this->db->selectRow("SELECT SQL_NO_CACHE `id`,`query_id`,`title` FROM `queryData` WHERE `pid_id`=? LIMIT 1",$this->id);
			// запуск нового объекта парса
			$return = new Parser('http://partner.market.yandex.ru/auction.xml?id='.$auth['shops_id'].'&pageSize=20&pageNum=1&q='.urlencode($data['title']) , $data['query_id'], $this->proxy);
			if ($return->aborted==true) {
				echo "aborted!<br/>";
			//дублирование задания
				$this->db->query("INSERT INTO `queryData` (`parse_id`,`pid_id`,`query_id`,`title`) VALUES (?,?,?,?)",$this->parse_id,$this->id,$data['query_id'],$data['title']);
				$rows_done=$rows_done-1;
			}
			// удаление задания по выполнению
			$this->db->query("DELETE FROM `queryData` WHERE `id`=? LIMIT 1",$data['id']);
			// увеличение пройденных заданий 
			$this->db->query("UPDATE `log_pid` SET `rows_done`=? WHERE `id`=?",$rows_done+1,$this->id);
		}
	}
	
	/**
	* контроллер для запуска парсера цен 
	*/
	private function priceParse(){
		switch ($this->platform) {
			case 1: $this->yandex_price();break;
			case 3: $this->abc_price();break;
			case 7: $this->priceru_price();break;
			default: $this->yandex_price();break;
		}
	}
	
	/**
	 * Парс цен на яндекс маркете
	 * Использует класс ParserPrice для парса
	 */
	private function yandex_price(){
		// Инициализация обьекта для получения страниц
		$this->proxy = new PageFinder($this->proxy_type,$this->region_id,false);
		while(1) {
			// Проверка принудительной остановки
			if($this->db->selectCell("SELECT `time_stop` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id)==='0')break;
			// Проверка наличия задания
			if($this->db->selectCell("SELECT SQL_NO_CACHE COUNT(*) FROM `queryPrice` WHERE `pid_id`=?",$this->id)==='0')break;
			// Получение кол-ва сделаных заданий
			$rows_done=$this->db->selectCell("SELECT SQL_NO_CACHE `rows_done` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id);
			// Получение задания
			$data=$this->db->selectRow("SELECT SQL_NO_CACHE * FROM `queryPrice` WHERE `pid_id`=? LIMIT 1",$this->id);
			// Проверка на не выполненость задания
			$sId=$this->db->selectRow("SELECT SQL_NO_CACHE * FROM `resultPrice` WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? AND `platform_id`=? LIMIT 1", $data['tovar_id'],$this->user_id,$this->region_id,$this->platform);
			// Если не выполненно
			if (sizeof($sId)==0){
				// Если стоит поиск по modelid
				if ($this->base==1)	{
					// получение кода маркета по коду товара
					$yandex_id=$this->db->selectCell("SELECT SQL_NO_CACHE `yandex_id` FROM `dataTovar_proof` WHERE `opt_id`=? LIMIT 1",$data['tovar_id']);
					// если нет кода маркета то ищем название товара в маркете
					if(!isset($yandex_id) OR $yandex_id==0)$yandex_title=$this->db->selectCell("SELECT SQL_NO_CACHE `title` FROM `dataTovar_nocard` WHERE `id`=? LIMIT 1",$data['tovar_id']);
					// запуск парсера по коду товара
					if(isset($yandex_id) && $yandex_id!=0)$return = new ParserPrice("http://market.yandex.ru/model.xml?modelid=".$yandex_id, $data['tovar_id'],$data['mvic'],$data['moic'],$data['MPI'],$this->proxy);
					// запуск парсера по названию товара
					elseif(isset($yandex_title) && $yandex_title!="") $return = new ParserPrice("http://market.yandex.ru/search.xml?text=".urlencode($yandex_title)."&cvredirect=1&onstock=1",$data['tovar_id'],$data['mvic'],$data['moic'],$data['MPI'],$this->proxy);
					else $rows_done=$rows_done-1;
				}
				// если обычный поиск по названию из файла
				else{
					// переменная для логирования названий товаров
					Registry::set_f('user_text',$data['title']);
					// запуск парсера
					$return = new ParserPrice("http://market.yandex.ru/search.xml?text=".urlencode($data['title'])."&cvredirect=1", $data['tovar_id'],$data['mvic'],$data['moic'],$data['MPI'],$this->proxy);
					// обработка ошибок
					if ($return->aborted==true) {
						echo "aborted!<br/>";
						$this->db->query("INSERT INTO `queryPrice` (`parse_id`,`pid_id`,`tovar_id`,`title`,`mvic`,`moic`,`MPI`) VALUES (?,?,?,?,?,?,?)",$this->parse_id,$this->id,$data['tovar_id'],$data['title'],$data['mvic'],$data['moic'],$data['MPI']);
						$rows_done=$rows_done-1;
					}
				}
			}
			// удаление выполненого задания из базы
			$this->db->query("DELETE FROM `queryPrice` WHERE `id`=? LIMIT 1",$data['id']);
			// увеличение количества выполненных заданий в базе
			$this->db->query("UPDATE `log_pid` SET `rows_done`=? WHERE `id`=?",$rows_done+1,$this->id);
		}
	}
	
	/**
	 * БАЗА НЕ ПОДХОДИТ ПОД МЕТОД
	 * Метод парса ABC.ru
	 */
	private function abc_price(){
		// Инициализация обьекта для получения страниц
		$this->proxy = new PageFinder('real','abc');
		// получение регионального кода для парса
		$region=Registry::get('db')->selectCell("SELECT `abc` FROM `regions` WHERE `id`=?",$this->region_id);
		// запуск обработки заданий
		while(1) {
			// Проверка принудительной остановки			
			if($this->db->selectCell("SELECT `time_stop` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id)==='0')break;
			// Проверка наличия задания
			if($this->db->selectCell("SELECT COUNT(*) FROM `queryPrice` WHERE `pid_id`=?",$this->id)==='0')break;
			$rows_done=$this->db->selectCell("SELECT `rows_done` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id);
			$data=$this->db->selectRow("SELECT * FROM `queryPrice` WHERE `pid_id`=? LIMIT 1",$this->id);
			$sId=$this->db->selectRow("SELECT COUNT(*) FROM `resultPrice` WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? AND `platform_id`=? LIMIT 1", $data['tovar_id'],$this->user_id,$this->region_id,$this->platform);
			if (sizeof($sId)==0){
				// получение названия товара
				$title=Registry::get('db')->selectCell("SELECT CONCAT(`brand`,' ',`model`) as `title` FROM `dataTovar_proof` WHERE `opt_id`=? LIMIT 1 UNION SELECT `title` FROM `dataTovar_nocard` WHERE `id`=? LIMIT 1",$key,$key);
				// формирование ссылки на товар
				$url='http://abc.ru/cgi-bin/prop/prop.pl?name=price'.urlencode($region).'&other='.urlencode($title);
				// запрос страницы товарв
				$page=$this->proxy->getpage($url);
				// изменение кодировки и стирание переносов каретки
				$page=iconv("CP1251", "UTF-8",$page);
				$page=preg_replace('/[\r\n]+/','',$page);
				// поиск ссылки на страницу с карточкой товара
				if(preg_match('/<a href=(http:\/\/[a-z]*.abc.ru\/[a-z0-9\/]*\/[a-z]*_[a-z0-9]*.htm)/', $page,$card)){
					// если существует, скачка страницы и чистка
					if(isset($card[1])) {
						$url=$card[1];
						$page=$this->proxy->getpage($url);
						$page=iconv("CP1251", "UTF-8",$page);
						$page=preg_replace('/[\r\n]+/','',$page);
					}
				}
				// выборка списка конкурентов
				preg_match_all('/<tr bgcolor="?(?:white|#F8F8F8)"?.*?>(.*?) p./',$page,$shops);
				$min=0;
				$max=0;
				$mid=0;
				$rows=Array();
				// обработка фрагментов кода с конкурентами
				foreach ($shops[1] as $val) {
					// получение цены и названия магазина 2 метода.
					if(preg_match('/node_name=.*?".*?r=(.*?)\//', $val, $name))	preg_match('/([0-9]*)$/', $val, $price);
					elseif (preg_match('/\&r=(.*?)[%\/]+/', $val, $name)) preg_match('/([0-9]*)$/', $val, $price);
					// если удачно спарсились цена и название
					if(isset($name[1]) && isset($price[1])){
						// поиск домена конкурента
						$domen=explode('.', $name[1]);
						$dc=count($domen);
						// формирование нормального названия конкурента
						$name[1]=$domen[$dc-2].".".$domen[$dc-1];
						// формирование строки для записи в базу
						$rows[]=join("|",Array($price[1],'Наличие',$name[1],0));
						// поиск минимальной, максимальной и средней цены
						if($min==0)$min=$price[1];
						elseif($price[1]<$min)$min=$price[1];
						if($price[1]>$max)$max=$price[1];
						$mid+=$price[1];
					}
					// если не подошло ничего под шаблоны то пишем ошибку в базу
					else Registry::get('db')->query("INSERT INTO `log_errors` VALUES (null,?,?,?,?,?)",$this->parse_id,time(),$data['tovar_id'],$url,'not mached to pattern');
				}
				// расчёт средней цены
				if(count($rows)>0)$mid=round($mid/count($rows));
				// запись данных в базу
				Registry::get('db')->query("INSERT INTO `resultPrice` VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",$this->user_id,$this->parse_id,$this->platform,$reg,$data['tovar_id'],$title,$url,$min,$max,$mid,$value[1],$value[2],$value[0],"",join("+",$rows));
			}
			// удаление выполненого задания
			$this->db->query("DELETE FROM `queryPrice` WHERE `id`=? LIMIT 1",$data['id']);
			// увеличение количества выполненных заданий в базе
			$this->db->query("UPDATE `log_pid` SET `rows_done`=? WHERE `id`=?",$rows_done+1,$this->id);
		}
	}
	
	/**
	 * БАЗА НЕ ПОДХОДИТ ПОД МЕТОД
	 * Метод парса Price.ru
	 */
	private function priceru_price(){
		$this->proxy = new PageFinder('real','priceru');
		$region=Registry::get('db')->selectCell("SELECT `abc` FROM `regions` WHERE `id`=?",$this->region_id);
		while(1) {
			if($this->db->selectCell("SELECT `time_stop` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id)==='0')break;
			if($this->db->selectCell("SELECT COUNT(*) FROM `queryPrice` WHERE `pid_id`=?",$this->id)==='0')break;
			$rows_done=$this->db->selectCell("SELECT `rows_done` FROM `log_pid` WHERE `id`=? LIMIT 1",$this->id);
			$data=$this->db->selectRow("SELECT * FROM `queryPrice` WHERE `pid_id`=? LIMIT 1",$this->id);
			$sId=$this->db->selectRow("SELECT COUNT(*) FROM `resultPrice` WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? AND `platform_id`=? LIMIT 1", $data['tovar_id'],$this->user_id,$this->region_id,$this->platform);
			if (sizeof($sId)==0){
				$title=Registry::get('db')->selectCell("SELECT CONCAT(`brand`,' ',`model`) as `title` FROM `dataTovar_proof` WHERE `opt_id`=? LIMIT 1 UNION SELECT `title` FROM `dataTovar_nocard` WHERE `id`=? LIMIT 1",$key,$key);
				$url='http://price.ru/search?query='.urlencode($title).$region;
				$page=$pf->getpage($url);
				$page=preg_replace('/[\r\n]+/','',$page);
				preg_match_all('/	<tr valign="top">	<td width="5%" valign="top"(.*?)<\/div><\/td><\/tr>/',$page,$shops);
				$min=0;
				$max=0;
				$mid=0;
				$rows=Array();
				foreach ($shops[1] as $val) {
					preg_match('/(?:\/[a-z0-9]+\.|\/)([a-z0-9\_\-]*\.[a-z]{2,3})\//', $val, $name);
					preg_match('/	([0-9]+)\&nbsp;р/', $val, $price);
					if(count($name)==0)preg_match('/<a href="\/firm\?id=[0-9]*">(.*?)</', $val, $name);
					if(isset($name[1]) && isset($price[1])){
						$domen=explode('.', $name[1]);
						$dc=count($domen);
						if($dc>1)$name[1]=$domen[$dc-2].".".$domen[$dc-1];
						$rows[]=join("|",Array($price[1],'Наличие',$name[1],0));						
						if($min==0)$min=$price[1];
						elseif($price[1]<$min)$min=$price[1];
						if($price[1]>$max)$max=$price[1];
						$mid+=$price[1];
					}
					else var_dump($url,$val);
				}
				if(count($rows)>0)$mid=round($mid/count($rows));
				Registry::get('db')->query("INSERT INTO `resultPrice` VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",$this->user_id,$this->parse_id,$this->platform,$reg,$data['tovar_id'],$title,$url,$min,$max,$mid,$value[1],$value[2],$value[0],"",join("+",$rows));
			}
			$this->db->query("DELETE FROM `queryPrice` WHERE `id`=? LIMIT 1",$data['id']);
			$this->db->query("UPDATE `log_pid` SET `rows_done`=? WHERE `id`=?",$rows_done+1,$this->id);
		}
	}
}
?>
