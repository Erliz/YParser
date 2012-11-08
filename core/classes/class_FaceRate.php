<?php
/**
 * Класс отвечающий за панель ставок для парсера.
 * Также через него проходит авторизация, но используются методы Face
 */
class FaceRate extends Face {
	
	/**
	 * Старая версия парсера работала через этот метод -_-
	 * @param $list - Список товаров/Названий для парсера
	 * @return none
	 */
	public function parse($list){}
	
	/**
	 * Запуск парсера по ставкам
	 * 
	 * @param string $file путь к файлу с брендами
	 * @param string $proxy тип прокси
	 * 
	 * @return bool
	 */
	public function start_daemon($file,$proxy=null){
		return $this->parser_daemon("rate",$file,$_SESSION['platform'],$proxy);
	}
	
	/**
	 * Запуск парсера по ставкам в отладочном режиме для прохода одной строки
	 * 
	 * @param array $list массив с данными для парса
	 * 
	 * @return bool
	 */
	public function parser_one_row($list=null){
		Registry::set('user_id', $_SESSION['user']);
		Registry::set('shops_id', $_SESSION['id']);
		Registry::set('proxy', $_SESSION['proxy']);
		$login=Registry::get('db')->selectCell("SELECT `login` FROM `shops` WHERE `id`=(SELECT `shop_id` FROM `shops_id` WHERE `id`=?) LIMIT 1",Registry::get('shops_id'));
		self::constan();
		$proxy= new PageFinder(Registry::get('proxy'),Registry::get('shops_id'));
		file_put_contents('pages/'.getmypid()."_rate.pid", ' ');
		$answer = $proxy->getpage("http://passport.yandex.ru/passport?mode=auth",false);
		if($answer==false) {echo "PageFinder return (false)!<br/>"; return false;}
		preg_match_all('/<input type="hidden" name="idkey" value="(.*?)" \/>.*/', $answer , $pm);
		var_dump('login='.urlencode($login).'&passwd='.$_GET['pass'].'&idkey='.$pm[0][0].'&retpath='.urlencode('http://partner.market.yandex.ru/').'&twoweeks=&In='.urlencode('Войти').'&timestamp='.time()."120");
		$proxy->curl_post('login='.urlencode($login).'&passwd='.$_GET['pass'].'&idkey='.$pm[0][0].'&retpath='.urlencode('http://partner.market.yandex.ru/').'&twoweeks=&In='.urlencode('Войти').'&timestamp='.time()."120");
		$res=$proxy->getpage('https://passport.yandex.ru/passport?mode=auth',false,'https://passport.yandex.ru/passport?mode=auth&retpath='.urlencode('http://partner.market.yandex.ru/'),true);
		if($res==false) {echo "PageFinder return (false)!<br/>"; return false;}		
		$proxy->curl_post(0);
		if(!$res) {
			file_put_contents(LOGS_PATH.'curl.log', $pid_id.": Ошибка при авторизации. ".$error."\r\n", FILE_APPEND);
			exit;
		}
		$return = new Parser('http://partner.market.yandex.ru/auction.xml?id='.Registry::get('shops_id').'&groupId=&q='.urlencode($list[0][1]).'&pageSize=20' , $list[1], $proxy);
		var_dump($return);
		if ($return->aborted==true) $this->message.='FAIL! parse 1 row! param: '.$list[0][2]." | ".$list[0][0]." | ".$list[0][1]." | ".$list[0][3]." (".$cost.")";
		else $this->message.='parse 1 row done!';
		unlink('pages/'.getmypid()."_rate.pid");
	}
	
	/**
	 * Проверяет наличие уже сгенерированного файла со ставками
	 * 
	 * @return string html код
	 */
	public function chkFile_rate() {
		$file='files/download/'.$_SESSION['id']."_".$_SESSION['platform']."_rate.csv";
		if (file_exists($file))
			$result="Ставки сформированы: ".date("d F H:i", filemtime($file));
		else
			$result="Ставки не сформированы";
		return $result;
	}
	
	/**
	 * Генерация ставок через класс RateGenerate
	 * Вывод ошибки по генерации в message
	 */
	public function generate_rate() {
		$rate=new RateGenerate();
		if ($rate->status==FALSE) $this->message.="Ставки сгенерировать не удалось!";
		elseif (strpos($rate->status, "error")!==false) {
			$error=explode(":", $rate->status);
			$this->message.="Ставки сгенерировать не удалось!<br/>";
			$this->message.="Ошибка: ".$error[1];
		}
		else $this->download($rate->status);
	}
	
	/**
	 * Скачивание файла со ставками
	 */
	public function download_rate(){
		$this->download('files/download/'.$_SESSION['shops']."_".$_SESSION['platform']."_rate.csv");
	}
	
	/**
	 * Скачивание файла с отчётом по ставкам
	 */
	public function download_fail(){
		$this->download('files/download/'.$_SESSION['shops']."_".$_SESSION['platform']."_dwl.csv");
	}
	
	/**
	 * Обновление данных по товарам для ставок из файла
	 * Название и Цена
	 * Вывод отчёта по загрузке в message
	 * 
	 * @param string $cont путь к файлу
	 */
	public function update($cont) {
		$rows=0;
		$upd=0;
		$new=0;
		$cont=fopen($cont, "r");
		while ($g[$rows]=fgetcsv($cont, 1000, ";")) $rows++;
		foreach ($g as $val) {
			if ($val[0]==null) continue;
			$exist=Registry::get('db')->selectCell("SELECT `id` FROM `tovar` WHERE `tovar_id`=? AND `shop_id`=? AND `platform_id`=? LIMIT 1", $val[0], $_SESSION['id'], $_SESSION['platform']);
			if ($exist>0) {
				Registry::get('db')->query("UPDATE `tovar` SET `title`=?,`cost`=? WHERE `id`=? LIMIT 1", $val[1], (int)$val[2], $exist);
				$upd++;
			}
			else {
				Registry::get('db')->query("INSERT INTO `tovar` (`shop_id`,`platform_id`,`tovar_id`,`title`,`cost`) VALUE (?,?,?,?,?)", $_SESSION['id'], $_SESSION['platform'], $val[0], $val[1], (int)$val[2]);
				$new++;
			}
		}
		$db_rows=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `tovar` WHERE `shop_id`=? AND `platform_id`=?", $_SESSION['id'],$_SESSION['platform']);
		$this->message.='ID в базе успешно обновлены!<br />В базе товаров: '.$db_rows.'<br />Обновлено: '.$upd.'<br/>Добавлено: '.$new.'<br/>Не загруженно: '.($rows - ($upd + $new)).'<br/>';
	}
	
	/**
	 * Обновление группы товаров из файла
	 * Вывод отчёта по загрузке в message
	 * 
	 * @param string $cont путь к файлу
	 */
	public function update_group($cont) {
		$rows=0;
		$upd=0;
		$new=array();
		$cont=fopen($cont, "r");
		while ($g[$rows]=fgetcsv($cont, 1000, ";"))	$rows++;
		foreach ($g as $val) {
			if ($val[0]==null)	continue;
			$exist=Registry::get('db')->select("SELECT `t`.`id` FROM `tovar` as `t` LEFT JOIN `shop_id` as `si` ON `si`.`id`=`t`.`shop_id` WHERE `t`.`tovar_id`=? AND `t`.`platform_id`=? AND `si`.`shop_id`=(SELECT `shop_id` FROM `shop_id` WHERE `id`=?)", $val[0], $_SESSION['platform'], $_SESSION['id']);
			if (count($exist)>0)
				foreach ($exist as $value) {
					Registry::get('db')->query("UPDATE `tovar` SET `tovar_group_id`=? WHERE `id`=?", $val[1], $value['id']);
					$upd++;
				}
			else $new[]=$val[0];
		}
		$db_rows=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `tovar` as `t` LEFT JOIN `shop_id` as `si` ON `si`.`id`=`t`.`shop_id` WHERE `t`.`shop_id`=(SELECT `shop_id` FROM `shop_id` WHERE `id`=?) AND `platform_id`=?", $_SESSION['id'], $_SESSION['platform']);
		$this->message.='ID в базе успешно обновлены!<br />В базе товаров: '.$db_rows.'<br />Обновлено: '.$upd.'<br/>';
		$this->message.=count($new)>0 ? 'Не найденные: <span style="font-size:8px;"'.join(',', $new).'</span><br/>' : '';
	}

	/**
	 * Генерация файла отчета по последнему парсу ставок
	 * Отдача фыйла на загрузку в браузер 
	 */
	public function generate_file() {
		$rows=Registry::get('db')->select("SELECT `tovar_id`,`title`,`yandex_id` FROM `tovar` LEFT JOIN `dataTovar_proof` ON `opt_id`=`tovar_id` WHERE `shop_id`=? AND `platform_id`=?", $_SESSION['id'],$_SESSION['platform']);
		if (count($rows)>0) {
			if (file_exists($this->file)) unlink($this->file);
			$hd=fopen($this->file, 'a');
			foreach ($rows as $row){
				if($row['yandex_id']!==null)$data=Registry::get('db')->selectRow("SELECT `title`,`minPrice`,`maxPrice`,`representCount` FROM `resultRate` WHERE `yandex_id`=? AND `shop_id`=? AND `platform_id`=? LIMIT 1",$row['yandex_id'], $_SESSION['id'],$_SESSION['platform']);
				if(!isset($data['minPrice']))$data=Registry::get('db')->selectRow("SELECT `title`,`minPrice`,`maxPrice`,`representCount` FROM `resultRate` WHERE `title` LIKE '%?%' AND `shop_id`=? AND `platform_id`=? LIMIT 1", $_SESSION['id'],$_SESSION['platform']);
				if (count($data)==4)
					$fields=Array(
							$row['tovar_id'],
							iconv("UTF-8", "CP1251", $row['title']),
							iconv("UTF-8", "CP1251", $data['title']),
							$data['minPrice'],
							$data['maxPrice'],
							$data['representCount']
					);
				else $fields=Array(
							$row['tovar_id'],
							iconv("UTF-8", "CP1251", $row['title']),
							'NO PARSE',
							0.12,
							0,
							0
					);
				fputcsv($hd, $fields, ';');
				unset($data);
			}
		}
		fclose($hd);
		$this->download('files/download/'.$_SESSION['shops'].'_'.$_SESSION['platform'].'_dwl.csv');
	}
	
	/**
	 * Выключение активности кнопок для панелей
	 * Основывается на внутренних проверках каждого класса
	 */
	public function setDisable() {
		$disabled='disabled="disabled"';
		$countYD=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `resultRate` WHERE `shop_id`=? AND `platform_id`=?", $_SESSION['id'],$_SESSION['platform']);
		$result["#FILE_DWL_DIS#"]=!file_exists($this->file) ? $disabled : "";
		$result["#FILE_GEN_DIS#"]=$countYD==0 ? $disabled : "";
		$result["#FILE_RATE_DWL_DIS#"]=!file_exists('files/download/'.$_SESSION['shops'].'_'.$_SESSION['platform'].'_rate.csv') ? $disabled : "";
		$result["#FILE_RATE_GEN_DIS#"]=$countYD==0 ? $disabled : "";
		$result["#CASH_CLEAN#"]=!glob('pages/'.$_SESSION['shops'].'/*') ? $disabled : "";
		return $result;
	}
	
	/**
	 * Проверка товаров на цену меньше 100р.
	 * Таких товаров в базе быть не должно, реклама по ним не идёт
	 * Вывод информации в message
	 */
	public function chkDb() {
		$count=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `tovar` WHERE `cost`>0 AND `cost`<100 AND `shop_id`=?", $_SESSION['id']);
		if ($count>100)
			$this->message.='В базе '.$count.' имеют цену меньше 100р.<br/>';
	}
	
	/**
	 * Recursively delete log file in directory
	 */
	public function cache_clean() {
		$dir='pages/'.$_SESSION['shops'].'/';
		$op_dir=opendir($dir);
		while ($file=readdir($op_dir))
			if ($file!="." && $file!="..")
				unlink($dir.$file);
		closedir($op_dir);
		$this->message.='Кэш '.$_SESSION['shops'].' успешно очищен!<br />';
	}

	/**
	 * Очистка базы от устаревших данных.
	 */
	public function truncate() {
		Registry::get('db')->query('DELETE FROM `resultRate` WHERE `shop_id`=? AND `platfrom_id`=?', $_SESSION['id'], $_SESSION['platform']);
		$count['resultRate']=Registry::get('db')->selectCell('SELECT COUNT(*) FROM `resultRate` WHERE `shop_id`=? AND `platfrom_id`=?', $_SESSION['id'], $_SESSION['platform']);
		if ($count['resultRate']==0)
			$this->message.="Очистка Базы от магазина {$_SESSION['id']} на площадке {$_SESSION['platform']} завершена успешно!<br />";
		else
			$this->message.="База не очищена от магазина {$_SESSION['id']} на площадке {$_SESSION['platform']}! В базе осталось {$count['resultRate']} строк!<br />";
	}
}
?>