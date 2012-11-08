<?php
/**
 * Класс отвечающий за панель цен для парсера.
 */
class FacePrice extends Face{
	
	/**
	 * Запуск парсера по ценам в отладочном режиме для прохода одной строки
	 * 
	 * @param array $list массив с данными для парса
	 * 
	 * @return bool
	 */
	public function parser_one_row($list=null) {
		Registry::set('user_id', $_SESSION['user']);
		Registry::set('region_id', $_SESSION['region']);
		Registry::set('platform_id', $_SESSION['platform']);
		Registry::set('proxy', $_SESSION['proxy']);
		Registry::set('parse_id', 0);
		self::constan();
		file_put_contents('pages/'.getmypid().".pid", ' ');
		
		$MPI=isset($list[0][2])? round(str_replace(",", ".", str_replace(" ","",$list[0][2])),2) : 0;
		$mvic=isset($list[0][3])? round(str_replace(",", ".", str_replace(" ","",$list[0][3])),2) : 0;
		$moic=isset($list[0][4])? round(str_replace(",", ".", str_replace(" ","",$list[0][4])),2) : 0;
		if(isset($_GET['base']) && $_GET['base']==true){
			$market_id=Registry::get('db')->selectCell("SELECT `yandex_id` FROM `dataTovar_proof` WHERE `opt_id`=?",$list[0][0]);
			$return = new ParserPrice("http://market.yandex.ru/model.xml?modelid=".$market_id,$list[0][0],$mvic,$moic,$MPI,new PageFinder('static',$_SESSION['region'],false));
		}
		else $return = new ParserPrice("http://market.yandex.ru/search.xml?text=".urlencode($list[0][1])."&cvredirect=1",$list[0][0],$mvic,$moic,$MPI,new PageFinder('static',$_SESSION['region'],false));
		if ($return->aborted==true) $this->message.='FAIL! parse 1 row! param: '.$list[0][1]." | ".$list[0][0]." | ".$list[0][2]." | ".$list[0][3]." (".$list[0][4].")";
		else $this->message.='parse 1 row done!';
		unlink('pages/'.getmypid().".pid");
	}

	/**
	 * Запуск парсера по ценам в отладочном режиме
	 * 
	 * @param array $list массив с данными для парса
	 * 
	 * @return bool
	 */
	public function parse($list) {
		$i=0;
		self::constan();
		Registry::set('parse_id', Registry::get('db')->query("INSERT INTO `log_parse` (`user_id`,`region_id`,`time_start`,`rows_plan`,`pid`) VALUES (?,?,?,?,?)", $_SESSION['user'], $_SESSION['region'], time(), sizeof($list), getmypid()));
		foreach ($list as $val) {
			unset($title);
			if (is_array($val)) {
				$sId=Registry::get('db')->selectRow('SELECT * FROM `resultPrice` WHERE `query_id`=? AND `user_id`=? AND `region_id`=? LIMIT 1', $val[0], $_SESSION['user'], $_SESSION['region']);
				if (sizeof($sId)==0) {
					if (isset($_POST['base'])) {
						$title=Registry::get('db')->selectCell("SELECT `title` FROM `title` WHERE `id`=?", $val[0]);
						if (isset($title))
							$val[1]=$title;
					}
					$cost=isset($val[2]) ? str_replace(",", ".", str_replace(" ", "", $val[2])) : 0;
					new ParserPrice("http://market.yandex.ru/search.xml?text=".urlencode($val[1])."&cvredirect=1", $val[0], $cost);
				}
				Registry::get('db')->query("UPDATE `log_parse` SET `rows_done`=`rows_done`+1 WHERE `id`=?", $_SESSION['parse_id']);
			}
		}
		Registry::get('db')->query("UPDATE `log_parse` SET `time_stop`=?, WHERE `id`=?", time(), $_SESSION['parse_id']);
		$this->message.='Парсинг прошёл успешно! Пройдено: '.$i.' из '.sizeof($list).' строк.<br/>';
		$parsers_id=Registry::get('db')->select("SELECT DISTINCT `parse_id` FROM `resultPrice` WHERE `user_id`=? `region_id`=?", $_SESSION['user'], $_SESSION['region']);
		$errors=Registry::get('db')->select("SELECT `title`, `url`, `value` FROM `log_tovar` WHERE `parse_id` in (".join(",", $parsers_id).")");
		if (count($errors)>0) {
			$this->message.='Были найдены ошибки в следующих запросах:<br/>';
			foreach ($errors as $key=>$value)
				$this->message.=$key.') '.$value['title'].' | '.$value['url'].' | '.$value['value'].'<br/>';
		}
	}
	
	/**
	 * Запуск парсера по ценам
	 * 
	 * @param string $file путь к файлу с товарами
	 * @param string $proxy тип прокси
	 * 
	 * @return bool
	 */
	public function start_daemon($file,$proxy = null){
		return $this->parser_daemon('price',$file,$_SESSION['platform'],$proxy);
	}
	
	/**
	 * Генерация файла цен через класс Download
	 * Вывод ошибки по генерации в message
	 */
	public function generate_file() {
		$report=new Download($_POST['type']);
		if ($report->status==FALSE) $this->message.="Отчёт сгенерировать не удалось!";
		elseif (strpos($report->status, "error")!==false) {
			$error=explode(":", $report->status);
			$this->message.="Отчёт сгенерировать не удалось!<br/>";
			$this->message.="Ошибка: ".$report[1];
		}
		else $this->download($report->status);
	}

	/**
	 * Вывод списка магазинов в radio form
	 * 
	 * @return string html код
	 */
	public function getShops(){
		$shops_list=Registry::get('db')->select('SELECT `id`,`name` FROM `shops` WHERE `price_5`>0 OR `price_10`>0');
		$shops='<br/><input type="radio" name="type" id="type_all" value="all" /><label for="type_all">Все</label>';
		foreach ($shops_list as $value)	$shops.='<br/><input type="radio" name="type" id="type_'.$value['id'].'" value="'.$value['id'].'" /><label for="type_'.$value['id'].'">'.$value['name'].'</label>';
		return $shops;
	}
	
	/**
	 * Выключение активности кнопок для панелей
	 * Основывается на внутренних проверках каждого класса
	 */
	public function setDisable() {
		$disabled='disabled="disabled"';
		$countYD=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `resultPrice` WHERE `user_id`=? AND `region_id`=?", $_SESSION['user'], $_SESSION['region']);
		$result["#FILE_DWL_DIS#"]=!file_exists($this->file) ? $disabled : "";
		$result["#FILE_GEN_DIS#"]=$countYD==0 ? $disabled : "";
		$result["#CASH_CLEAN#"]=!glob('pages/'.$_SESSION['region'].'/*') ? $disabled : "";
		return $result;
	}

	/**
	 * Очистка базы от устаревших данных.
	 */
	public function truncate() {
		Registry::get('db')->query('DELETE FROM `resultPrice` WHERE `user_id`=? AND `region_id`=?', $_SESSION['user'], $_SESSION['region']);
		$count['resultPrice']=Registry::get('db')->selectCell('SELECT COUNT(*) FROM `resultPrice` WHERE `user_id`=? AND `region_id`=?', $_SESSION['user'], $_SESSION['region']);
		if ($count['resultPrice']==0)
			$this->message.="Очистка resultPrice региона {$_SESSION['region']} завершена успешно!<br />";
		else
			$this->message.="Очистка resultPrice региона {$_SESSION['region']} НЕ ЗАВЕРШЕНА! В базе осталось {$count['resultPrice']} строк<br />";
	}
	
	/**
	 * Проверка наличия кодов товаров из файла в базе с id
	 * Вывод в message информации по списку кодов
	 * 
	 * @param string $fp путь к файлу
	 * 
	 * @return bool true
	 */
	public function chkTovar($fp){
		$list=file_get_contents($fp);
		$list=explode("\r\n", $list);
		$base=Registry::get('db')->selectCol("SELECT `data`.`opt_id` FROM (SELECT `opt_id` FROM `dataTovar_proof` UNION SELECT `id` FROM `dataTovar_nocard`) as `data` WHERE `opt_id` in (?a)", $list);
		$diff=array_diff($list,$base);
		if(count($diff)==0) $this->message="В базе есть весь список!";
		else $this->message="В базе нет ".count($diff)." артикулов:<br/>".join('<br/>', $diff);
		return true;
	}
}
?>