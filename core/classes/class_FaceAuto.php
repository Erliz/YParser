<?php
/**
 * Класс для работы с панелью РУБКА
 * Изначально предполагался как класс отвечающий за всю автоматизацию процесса.
 */
class FaceAuto extends Face {
	// Файл для цен (Без расширения и указания региона)
	private $fp_price='files/autoprice';
	// Файл с брендами для автоставок.
	private $fp_rate='files/autorate.csv';
	
	/**
	 * Обновление файла
	 * 
	 * @param string $file путь к файлу
	 * @param string $cont путь к файлу
	 * @return bool
	 */
	private function update_file($file,$cont){
		if(file_exists($file)) unlink($file);
		if (move_uploaded_file($cont, $file)){
			$this->message.='Файл '.$file.' Обновлён!';
			return true;
		}
		else{
			$this->message.='Файл '.$file.' НЕ Обновлён!!!! ';
			return false;
		}
	}
	
	/**
	 * Обновления файла для парсера цен
	 * 
	 * @param string $cont путь к файлу
	 * @param int $reg id региона
	 * @return bool
	 */
	public function update_price($cont,$reg) {
		return $this->update_file($this->fp_price."_".$reg.".csv",$cont);
	}
	
	/**
	 * Обновления файла для парсера ставок
	 * 
	 * @param string $cont путь к файлу
	 * @return bool
	 */
	public function update_rate($cont) {
		return $this->update_file($this->fp_rate,$cont);
	}
	
	/**
	 * Превращяет одномерный массив в список через '<br />'
	 * Нахрена.... если можно использовать join или тег <pre></pre>
	 * 
	 * @param array $arr одномерный массив с данными
	 * @return string данные построчно в html
	 */
	private function array_print($arr){
		$result='';
		foreach ($arr as $value) $result.=$value.'<br/>';
		return $result;
	}
	
	/**
	 * Вытаскивает из консоли НЕ закоментированные строки крона, и показывает время из них.
	 * 
	 * @param string $grep условие по которому осуществляется поиск строк
	 * @return string строка с времеными метками
	 */
	private function cron_task($grep){
		exec("crontab -l | grep ".$grep,$result);
		preg_match_all('/[^#]([0-9]{1,2})[ 	]+([0-9]{1,2})[ 	]+\*[ 	]+\*[ 	]+\*/', join($result,"\r\n"), $times,PREG_SET_ORDER);
		$result='';
		foreach ($times as $value) $result.=$value[2].":".$value[1]."&nbsp&nbsp&nbsp&nbsp";
		return $result;
	}
	
	/**
	 * Поиск файлов в директории files/history/ удовлетворяющие условию и вывод их в виде ссылочного списка
	 * 
	 * @param string $pattern условие по которому осуществляется поиск файлов
	 * @param string $ext асширение файла (по умолчанию txt)
	 * @return string html список с сылками на файлы
	 */
	private function filelist($pattern, $ext='txt'){
		$list='';
		$files=array_reverse(glob('files/history/*'.date("m_Y",time()).' *_'.$pattern.'_*.'.$ext));
		for($i=0;$i<10;$i++){
			if(!isset($files[$i]))break;
			$val=explode('/', $files[$i]);
			$list.='<a style="color:inherit;" href="'.$files[$i].'">'.$val[2].'</a><br/>';
		}
		return $list;
	}
	
	/**
	 * Список Кодов товаров из занесённых списков в базу
	 * 
	 * @return array одномерный массив со списком кодов
	 */
	public function tovarlist_price(){
		return Registry::get('db')->selectCol("SELECT `opt_id` FROM `dataTovar_proof` UNION SELECT `id` FROM `dataTovar_nocard`;"); 
	}
	
	/**
	 * Список кодов кондиционеров которые собраны из 2х товаров внутреннего блока и внешнего.
	 * 
	 * @return array массив с кодами кондеев
	 */
	public function unionlist(){
		$list=Registry::get('db')->select("SELECT * FROM `dataTovar_union`");
		$return=Array();
		foreach ($list as $value) {
			$return[0][]=$value['id'];
			$return[1][]=$value['id2'];
		} 
		return $return;
	}
	
	/**
	 * Вытаскивает из файлов для парса цен всех регионов значения
	 * 
	 * @return array 2ву мерный массив
	 */
	private function TovarFileList_price(){
		$list=Array();
		foreach (Registry::get('db')->selectCol("SELECT `id` FROM `regions`") as $value) {
			$handle=fopen($this->fp_price."_".$value.".csv", "r");
			$rows=0;
			while ($list[]=fgetcsv($handle, 1000, ";")) $rows++;
			fclose($handle);
		}
		return $list;
	}
	
	/**
	 * Сравнение кодов из файла для парса и существующими кодами из базы
	 * Показывает совпадающие коды.
	 * 
	 * @return string список кодов в html
	 */
	public function TovarListIntersect_price(){
		$all=$this->tovarlist_price();
		foreach ($this->TovarFileList_price() as $value) $file[]=$value[0];
		$file=array_unique($file);
		return $this->array_print(array_intersect($all, $file));
	}
	
	/**
	 * Проверка файла для автопарса цен.
	 * 
	 * @return string сообщение 
	 */
	public function chkFile_autoprice($reg) {
		return $this->chkFile($this->fp_price."_".$reg.".csv");
	}
	
	/**
	 * Проверка файла с брендами для автопарса ставок.
	 * 
	 * @return string сообщение 
	 */
	public function chkFile_autorate() {
		return $this->chkFile($this->fp_rate);
	}
	
	/**
	 * Информация из крона для автопарса ставок
	 * 
	 * @return string временные отметки
	 */
	public function cron_rate() {
		return $this->cron_task('autorate');
	}
	
	/**
	 * Информация из крона для автопарса статистики по площадкам
	 * 
	 * @return string временные отметки
	 */
	public function cron_stat() {
		return $this->cron_task('cron_stat');
	}
	
	/**
	 * Информация из крона для автопарса цен
	 * 
	 * @return string временные отметки
	 */
	public function cron_price() {
		return $this->cron_task('autoprice');
	}

	/**
	 * Поиск файлов с выставленными ценами
	 * 
	 * @return string ссылочный список
	 */
	public function filelist_price(){
		return $this->filelist('price');
	}
	
	/**
	 * Поиск файлов с выставленными ставками
	 * 
	 * @return string ссылочный список
	 */
	public function filelist_rate(){
		return $this->filelist('rate');
	}
	
	/**
	 * Выводит html интерфейс для заливки цен из одного магазина в другой
	 * Можно делать легкие арифметические действия с ценами
	 * 
	 * @return string код html
	 */
	public function forcesv(){
		// Список файлов с выставленными ценами в обратном порядке
		$files=array_reverse(glob('files/history/*'.date("m_Y",time()).' *_price_*.txt'));
		// Формирование html строки
		$html='<br/>##############<br/><form action="" method="post"><select name="from">';
		// Список файлов
		foreach($files as $val) $html.='<option value="'.$val.'">'.$val.'</option>';
		$html.='</select><input type="radio" name="action" value="plus" id="plus"/><label for="plus">+</label>
		<input type="radio" name="action" value="minus" id="minus"/><label for="minus">-</label>
		<input type="text" name="num" size="4" value="" />';
		$html.='<select name="to">';
		// Вытаскивание кода городов для заливки в SV
		foreach(Registry::get('db')->select("SELECT `sv_id`,`sv_login` FROM `shop_id`") as $val) $html.='<option value="'.$val['sv_id'].'">'.$val['sv_login'].'</option>';
		$html.='</select><input type="submit" name="submit" value="Залить цены" /><br/>##############<br/>';
		
		return $html;
	}
	
	/**
	 * Заливка цен из одного магазина в другой
	 * 
	 * @param string $from имя файла с ценами
	 * @param int $to код города SV
	 * @param int $num значение для цены
	 * @param enum(plus,minus) $action действие с ценой и значением для цены
	 * 
	 * @return bool|int возвращяет число, при имеющихся не совпадениях
	 */
	public function fastsv_update($from,$to,$num,$action){
		if(!file_exists($from)){
			$this->message.="Такого файла ($from) не существует!";
			return false;
		}
		$fp='/mnt/dc-sql3/BulkInsert/priceInet.txt';
		$rows=Array();
		$report=Array();
		$rows=0;
		$new=Array();
		// Извлечение информации из файла
		$cont=fopen($from, "r");
		while ($list[$rows]=fgetcsv($cont, 1000, "	")) $rows++;
		fclose($cont);
		array_shift($list);
		// Обработка цен
		foreach ($list as $val) {
			if(!isset($val[1])) continue;
			$price=$val[1];
			// Преобразование цен
			if($action=="plus")$price=$val[1]+(int)$num;
			elseif($action=="minus")$price=$val[1]-(int)$num;
			// Формированик массива
			$new[]=Array($to,$val[0],$price);
		}
		// Очистка файла для заливки в SV
		if(file_exists($fp)) unlink ($fp);
		$handle=fopen($fp, "a");
		// Занос информации в файл
		foreach ($new as $row) fputcsv($handle, $row, '	');
		fclose($handle);
		// Добавление перевода коретки в файл
		$convert=file_get_contents($fp);
		$convert=str_replace("\n", "\r\n", $convert);
		file_put_contents($fp, $convert);
		// Сохранение копии файла в историю
		copy($fp,"files/history/".date("d_m_Y H:i",time())."_price_to_".$to.'.txt');
		// Заливка файла в SV
		$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!'); 
	    if(!isset($conn)) echo "Can't connect to Microsoft SQL Server!";
		mssql_select_db('uchet', $conn);
		$result = mssql_query("EXEC cllc_SetPricesFromParser");
		// Проверка на запись данных
		if(isset($result) ){
			while($line = mssql_fetch_assoc($result)) $report[$line['tovar']]=$line['value'];
			if(count($rows)==count($report)) return true;
			else return (count($rows)-count($report));
		}
		else {
			echo "mssql_query fail!!!!!!";
			return false;
		}
		
	}
	
	/**
	 * НИГДЕ НЕ ИСПОЛЬЗУЮЕТСЯ!
	 * Создание отчета по ценам
	 * 
	 * @param int $uid id пользователя
	 * @param int $reg id города
	 */
	public static function report($uid,$reg){
		$shops=Registry::get('db')->select("SELECT `si`.`shop_id`,`sv_id`,`sv_login`,`name`,`price_5`,`price_10` FROM `shop_id` as `si` LEFT JOIN `shops` as `s` ON `si`.`shop_id`=`s`.`id` WHERE `si`.`region`=?",$reg);
		$query = Registry::get('db')->select("SELECT `yp`.`tovar_id`,`yp`.`name`,`yp`.`five_shop`,`yp`.`ten_shop`,`n`.`tip` FROM `resultPrice` as `yp` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`yp`.`tovar_id` WHERE `user_id`=? AND `region_id`=?",$uid,$reg);
		if (count($query)==0) return 'Таблица пустая!<br/>';
		foreach ($shops as $shop) {
			if($shop['price_5']>0) {$top='five_shop';$position="blok";}
			elseif($shop['price_10']>0) {$top='ten_shop';$position="nal";}
			else {echo 'Для магазина '.$shop['name'].' не установлено позиционирование!<br/>';continue;}
			$file_path='files/history/'.date("d_m_Y H:i",time()).'_'.$shop['sv_login'].'_'.$position.'_report.csv';
			if (file_exists($file_path)) unlink($file_path);
			$fp=fopen($file_path, "a");
			if($position=='blok') $row=array('Код','Бренд','Наименование','Тип','Цена','По Цене','Позиция','Магазин');
			else $row=array('Код','Бренд','Наименование','Тип','Цена','Позиция','Магазин');
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
			foreach ($query as $key => $value) {
				$brand=explode(' ',$value['name']);
				if(strlen($value[$top])==0) continue;
				$list=explode('+',$value[$top]);
				foreach ($list as $ke=>$sh)	$list[$ke]=str_replace(chr(194).chr(160),"",$sh);
				natsort($list);
				$count=0;
				foreach($list as $k=>$mags){
					$count++;
					if (strlen($mags)<9) continue;
					$mag=explode('|',$mags);
					if(stristr($shop['name'], $mag[2])===false) continue;
					$row=Array();
					$row[0]=$value['tovar_id'];
					$row[1]=$brand[0];
					$row[2]=$value['name'];
					$row[3]=$value['tip'];
					$row[4]=$mag[0];
					if($position=='blok') $row[5]=$count;
					$row[6]=1+$k;
					$row[7]=$mag[2];
					foreach($row as $key=>$val)$row[$key]=Download::decode($val);
					fputcsv($fp, $row, ";");
				}
			}
		fclose($fp);
		}
	}

	/**
	 * Создание отчёта о позиционировании товаров на Яндекс Маркете
	 * Для загрузки в ОЛАП
	 * 
	 * @param int $uid id пользователя
	 * @param int $reg id города
	 * 
	 * @return int количество добавленных записей
	 */
	public static function report_olap($uid,$reg){
		$shops=Registry::get('db')->select("SELECT `si`.`shop_id`,`olap_id`,`name`,`price_5`,`price_10` FROM `shop_id` as `si` LEFT JOIN `shops` as `s` ON `si`.`shop_id`=`s`.`id` WHERE `si`.`region`=?",$reg);
		$query = Registry::get('db')->select("SELECT `yp`.`tovar_id`,`yp`.`name`,`yp`.`five_shop`,`yp`.`ten_shop`,`n`.`tip` FROM `resultPrice` as `yp` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`yp`.`tovar_id` WHERE `user_id`=? AND `region_id`=?",$uid,$reg);
		$date=date("Ymd", time());
		if (count($query)==0) return 'Таблица пустая!<br/>';
		$conn = mssql_connect ('192.168.71.5:1433', 'usr_Int', 'usrintpwd') or die ("Can't connect to Microsoft SQL Server");
		mssql_select_db('OLAP_DATA_int', $conn) or die ("Can't select databes");
		$added=0;
		foreach ($shops as $shop) {
			if($shop['olap_id']==0) {echo 'Для магазина '.$shop['name'].' не назначен olap_id';continue;}
			$rows = mssql_result(mssql_query("SELECT COUNT(*) FROM t_SiteYandexPlace WHERE Date='".$date."' AND SiteCode=".$shop['olap_id']),0,0);
			if($rows>0) {echo 'Для магазина '.$shop['name'].' уже произведена сегодня заливка! Строк: '.$rows.'<br/>'; continue;}
			if($shop['price_5']>0) {$top='five_shop';$position="blok";}
			elseif($shop['price_10']>0) {$top='ten_shop';$position="nal";}
			else {echo 'Для магазина '.$shop['name'].' не установлено позиционирование!<br/>';continue;}
			foreach ($query as $key => $value) {
				if(strlen($value[$top])==0) continue;
				$list=explode('+',$value[$top]);
				foreach ($list as $ke=>$sh)	$list[$ke]=str_replace(chr(194).chr(160),"",$sh);
				natsort($list);
				$count=0;
				foreach($list as $k=>$mags){
					$count++;
					if (strlen($mags)<9) continue;
					$mag=explode('|',$mags);
					if(stristr($shop['name'], $mag[2])===false) continue;
					mssql_query("INSERT t_SiteYandexPlace (Date,SiteCode,tovar,Value) VALUES ('".$date."',".$shop['olap_id'].",".$value['tovar_id'].",".($k+1).")");
					$added++;
				}
			}
		}
		mssql_close();
		return $added;
	}

	/**
	 * Интерфейс для заливки стоп позиций товаров
	 * Эти товары не будут обрабатываться парсером.
	 * 
	 * @return string html код
	 */
	public function forcestop(){
		$shop=Array();
		$tovar=Array();
		// Список магазинов
		$shops=Registry::get('db')->select("SELECT `id`,`sv_login` FROM `shop_id` ORDER BY `sv_login`");
		// Формирование html
		$html='<br/>##############<br/><form action="" method="post" enctype="multipart/form-data"><input type="checkbox" id="all" name="shops" value="all"><label for="all">ВСЕ!</label><br/>';
		foreach($shops as $val) {
			$shop[$val['id']]=$val['sv_login'];
			$html.='<input type="checkbox" id="'.$val['id'].'" name="shops[]" value="'.$val['id'].'" />
			<label for="'.$val['id'].'">'.$val['sv_login'].'</label><br/>';
		}
		$html.='<input type="text" name="id" size="9" value=""><br/><input type="file" name="list" /><br/><br/>';
		$html.='<input type="submit" name="submit" value="Залить стоп позиции" /><br/>##############<br/>';
		// Список товаров в стоп листе
		$list=Registry::get('db')->select("SELECT `id`,`shop_id` FROM `dataTovar_stop`");
		$html.='<form action="" method="post"><span>В стоп листе '.count($list).' ('.round(count($list)/count($shops)).') позиций!<br/><table>';
		// Генерация таблицы с товарами и магазинами из стоп листа
		if(count($list)!=0){
			foreach($list as $value) $tovar[$value['id']][]=$value['shop_id'];
			foreach ($tovar as $key => $value) {
				$html.='<tr><td><input type="checkbox" name="tovar[]" value="'.$key.'" /></td><td>'.$key.'</td>';
				foreach ($shop as $k=>$val){
					if(in_array($k, $value)) $img='offline.png';
					else $img='online.png';
					$html.='<td><img src="style/img/'.$img.'" title="'.$val.'" /> </td>';
				}
				$html.='</tr>';
			}
			$html.='</table><br/>
				<input type="submit" name="submit" value="Удалить товар" /><br/><br/>
				<input type="submit" name="submit" value="Удалить ВСЁ!!!" /></form>
				<br/> ##############<br/>';
		}
		return $html;
	}

	/**
	 * Добавление товара в стоп лист
	 * 
	 * @param array|string $id товар
	 * @param array|string список магазинов или слово all (значит все магазины)
	 * 
	 * @return bool|string строку с ошибкой, если есть.
	 */
	public function stop_add($id,$shops){
		// проверка на то что Код товара это цифры
		if(!preg_match('/[0-9]+/',$id)) return "$id is not an Integer!";
		if(!is_array($shops) && $shops!='all') $shops=Array($shop);		
		if($shops=='all'){
			foreach(Registry::get('db')->selectCol("SELECT `id` FROM `shop_id`") as $val)
				Registry::get('db')->query("INSERT INTO `dataTovar_stop` (`id`,`shop_id`) VALUES (?,?)",$id,$val);
		}
		elseif(is_array($shops))
			foreach ($shops as $val)
				Registry::get('db')->query("INSERT INTO `dataTovar_stop` (`id`,`shop_id`) VALUES (?,?)",$id,$val);
		else return "$shops is not an array!";
		return true;
	}
	
	/**
	 * Удаление товаров из стоп листа магазинов
	 * 
	 * @param array|string $ids список товаров или слово all (значит все товары)
	 * @param array|string список магазинов или слово all (значит все магазины)
	 * 
	 * @return bool|string строку с ошибкой, если есть.
	 */
	public function stop_delete($ids,$shops){
		if($ids!='all' && !is_array($ids)) return "$id is not an Array or string 'all'!";
		if(!is_array($ids))$ids=array($ids);
		if(!is_array($shops) && $shops!='all') $shops=Array($shop);
		foreach ($ids as $id) {
			if($shops=='all'){
				if($id=='all') Registry::get('db')->query("TRUNCATE TABLE `dataTovar_stop`");
				else Registry::get('db')->query("DELETE FROM `dataTovar_stop` WHERE `id`=?",$id);
			}
			elseif(is_array($shops)){
				foreach ($shops as $val){
					if($id=='all') Registry::get('db')->query("DELETE FROM `dataTovar_stop` WHERE `shop_id`=?",$val);
					else Registry::get('db')->query("DELETE FROM `dataTovar_stop` WHERE `shop_id`=? AND `id`=?",$val,$id);
				}
			}
			else return "$shops is not an array!";
		}
		return true;
	}

	/**
	 * Заливка товаров в стоп лист через файл
	 * 
	 * @param string $fp путь к файлу со списоком товаров
	 * @param array|string список магазинов или слово all (значит все магазины)
	 * 
	 * @return bool|string строку с ошибкой, если есть.
	 */
	public function stop_file($fp,$shops){
		if(!file_exists($fp)) return "file in $fp doesn`t exists!";
		$cont=fopen($fp, "r");
		$return=Array();
		$result='';
		while ($tovar=fgetcsv($cont, 1000, ";")) $return[$tovar[0]]=$this->stop_add($tovar[0], $shops);
		fclose($cont);
		foreach ($return as $key => $value)	if($value!=true)$result.=$key.': '.$value.'<br/>';
		if($result='') return true;
		else return $result;
	}
	
	/**
	 * Проверка кода товара на присутствие в базах и вывод информации о нём
	 * НЕОБХОДИМО ДОБАВИТЬ ЧАСТЬ ФУНКЦИОНАЛА в  add_card
	 * 
	 * @param int $id код товара
	 * 
	 * @return string html код
	 */
	public function check_id($id){
		$id=Registry::get('db')->selectRow("SELECT `opt_id` as `id`,1 as `tbl` FROM `dataTovar_proof` WHERE `opt_id`=? UNION SELECT `id`,2 as `tbl` FROM `dataTovar_nocard` WHERE `id`=? UNION SELECT `id2` as `id`,3 as `tbl` FROM `dataTovar_union` WHERE `id2`=?",$id,$id,$id);
		if(count($id)>0){
			switch ($id['tbl']) {
				case '1': $tovar=Registry::get('db')->selectRow("SELECT * FROM `dataTovar_proof` WHERE `opt_id`=? LIMIT 1",$id['id']);
							$result="ID: {$id['id']}<br/>YID: {$tovar['yandex_id']}<br/>Brand: {$tovar['brand']}<br/>Model: {$tovar['model']}<br/>";break;
				case '2': $tovar=Registry::get('db')->selectRow("SELECT * FROM `dataTovar_nocard` WHERE `id`=? LIMIT 1",$id['id']);
							$result="ID: {$id['id']}<br/>YID: no card<br/>Title: {$tovar['title']}<br/>";break;
				case '3': $tovar=Registry::get('db')->selectRow("SELECT * FROM `dataTovar_union` WHERE `id2`=? LIMIT 1",$id['id']);
							$result="ID: {$id['id']}<br/>Внешний блок id: {$tovar['id']}<br/>";break;
				default: return 'shit happens...';
			}
			$stop=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `dataTovar_stop` WHERE `id`=? GROUP BY `id`",$id['id']);
			if($stop>0)$result.='STOP лист: '.$stop.'магазинов<br/>';
		}
		else $result="Товара в базе нет!";
		return $result;
	}
	
	/**
	 * Поиск файлов с собранными данными из площадок
	 * 
	 * @return string ссылочный список
	 */
	public function filelist_stat(){
		return $this->filelist('stat','csv');
	}
	
	/**
	 * Поиск файлов с ценами МВИЦ позиций
	 * Где цены конкурентов, под которых мы можем встать, стоят выше МВИЦа более чем на 10% 
	 * 
	 * @return string ссылочный список
	 */
	public function errorlist_price(){
		return $this->filelist('hand');
	}
	
	/**
	 * Статистика по площадкам
	 * Количество площадок, аккаунтов и магазинов
	 * 
	 * @return string html код 
	 */
	public function basestat_stat(){
		$stat=Registry::get('db')->selectCol("SELECT COUNT(*) FROM `platform` UNION SELECT COUNT(*) FROM `platform_acc` UNION SELECT COUNT(*) FROM `platform_shops`");
		return "Площадок: {$stat[0]}<br/>Аккаунтов: {$stat[1]}<br/>Магазинов: {$stat[2]}";
	}
	
	// Функции требуеммые из-за наследования Face
	public function setDisable() {}
	public function parse($list) {}
	public function start_daemon($file){}
	public function parser_one_row($list=null){}
	public function truncate() {}
	public function generate_file() {}
}
?>