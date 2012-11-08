<?php
/**
 * Abstract class of user interfaces classes
 * include all interfaces functions and utilities
 */
abstract class Face {
	// сообщение появляющееся на верху панели красным цветом
	public $message='';
	// файл с которым суждено работать классу
	public $file='';

	/**
	 * Абстрактный метод расчитаный на парс 1 строки - для Debug
	 */
	abstract public function parser_one_row($list=null);
	
	/**
	 * Запуск демона парса
	 * @param $file - поидее тут не файл передаваться должен о_0
	 * @return bool
	 */
	abstract public function start_daemon($file);
	
	/**
	 * Старая версия парсера работала через этот метод -_-
	 * @param $list - Список товаров/Названий для парсера
	 * @return none
	 */
	abstract public function parse($list);
	
	/**
	 * Генерация файла для отчета
	 * @return string файл с запросом на загрузку
	 */
	abstract public function generate_file();
	
	/**
	 * Выключение активности кнопок для панелей
	 * Основывается на внутренних проверках каждого класса
	 */
	abstract public function setDisable();
	
	/**
	 * Очистка базы от устаревших данных.
	 */
	abstract public function truncate();
	
	/**
	 * BIG HEAVY UGLY authorization + matrix of using parsers.
	 * тут пздц короче -_-
	 * обработка авторизации и вывод матрици работы парсеров, кнопок панелей.
	 * @return bool - оно не возвращяет... оно сразу выводит.
	 */
	public function auth() {
		// Проверка на валидность данных для авторизации в Ставках. Пароль используется только там.
		if (isset($_POST['id']) && isset($_POST['pass']) && $_POST['pass']!='' && isset($_POST['platform'])) {
			// Запрос в базу за данными
			$shop=Registry::get('db')->selectRow("SELECT `ps`.`shops_id`,`pa`.`login`,`pa`.`passwd` FROM `platform_shops` as `ps` LEFT JOIN `platform_acc` as `pa` ON `pa`.`id`=`ps`.`platform_acc_id` WHERE `ps`.`shop_id`=? AND `pa`.`platform_id`=? LIMIT 1", $_POST['id'], $_POST['platform']);
			// Кара за неправду -> переадресация на стартовую страницу.
			if(count($shop)==0){
				unset($_SESSION['parser']);
				header("location: index.php");
				exit;
			}
			// Занос данных в Сессийную переменную.
			$_SESSION['id']=$_POST['id'];
			$_SESSION['shops']=$shop['shops_id'];
			$_SESSION['platform']=$_POST['platform'];
			$_SESSION['login']=$shop['login'];
			$_SESSION['pass']=$shop['passwd'];
			// Вау о_0 возврат
			return true;
		}
		if (file_exists("templates/auth.tpl")) $html=file_get_contents("templates/auth.tpl");
		else {
			echo 'TPL login not Found!';
			exit ;
		}
		// INIT PLATFORMS
		$list_pl_price=Registry::get('db')->select("SELECT `id`,`title` FROM `platform` WHERE `price_flag`=1");
		$plat_price='';
		foreach ($list_pl_price as $value) $plat_price.='<option value="'.$value['id'].'">'.$value['title'].'</option>';
		/*$list_pl_rate=Registry::get('db')->select("SELECT `id`,`title` FROM `platform` WHERE `rate_flag`=1");
		$plat_rate='';
		foreach ($list_pl_rate as $value) $plat_rate.='<option value="'.$value['id'].'">'.$value['title'].'</option>';
		// END INIT PLATFORMS
		
		// INIT SHOPS
		$list_sh=Registry::get('db')->select("SELECT `si`.`id` as `id`,`s`.`name` as `name`,`r`.`name` as `region` FROM `shop_id` as `si` LEFT JOIN `shops` as `s` ON `s`.`id`=`si`.`shop_id` LEFT JOIN `regions` as `r` ON `r`.`id`=`si`.`region` ORDER BY `s`.`name`,`r`.`name`");
		$shops='';
		foreach ($list_sh as $value) $shops.='<option value="'.$value['id'].'">'.$value['name'].' ('.$value['region'].')</option>';
		// END INIT SHOPS
		// Сборка матрици по ставкам
		$stat_sh_filler='';
		$stat_sh='
			<tr align="center">
			<td rowspan="2">Маг\Площ</td>';
		foreach ($list_pl_rate as $value){
			$stat_sh.='<td colspan=2>'.$value['title'].'</td>';
			$stat_sh_filler.='<td></td><td>Товаров</td>';
		}
		$tovars=Array();
		$stat_sh.='</tr><tr align="center">'.$stat_sh_filler.'</tr>';
		$tov=Registry::get('db')->select("SELECT COUNT(*) as `count`,`platform_id`,`shop_id` FROM `resultRate` GROUP BY `shop_id`,`platform_id`");
		$tov_yan=Registry::get('db')->select("SELECT COUNT(*) as `count`,`platform_id`,`shop_id` FROM `resultRate` WHERE `yandex_id`>0 GROUP BY `shop_id`,`platform_id`");
		foreach ($tov as $key => $value) $tovars[$value['shop_id']][$value['platform_id']]=Array($value['count'],$tov_yan[$key]['count']);		
		foreach ($list_sh as $value) {
			$stat=Registry::get('db')->selectRow("SELECT `lp`.`time_stop`,`lp`.`rows_plan`,`lp`.`rows_done`,`lp`.`pid` FROM `log_parse` as `lp` WHERE `lp`.`user_id`=? AND `lp`.`type`='rate' ORDER BY `lp`.`id` DESC LIMIT 1", $value['id']);
			if(count($stat)==0) continue;
			// проставление картинок статуса работы
			$img=isset($stat['time_stop']) ? 'offline.png' : 'online.png';
			$img=isset($stat['time_start']) ? $img : 'offline.png';
			// Обрезание названий магазинов и выставление времени последнего парса, если запускался
			$stat_sh.='<tr align="center">
			<td>'.$value['name'].'('.mb_substr($value['region'],0,3,"UTF-8").')</td>
			<td><img src="style/img/'.$img.'" title="'.(isset($stat['time_stop']) ? date("d.m H:i", $stat['time_stop']) : "Не запускался!").'" /></td>';
			foreach ($list_pl_rate as $val)	$stat_sh.='<td>'.(isset($tovars[$value['id']][$val['id']])?$tovars[$value['id']][$val['id']][0].'/'.@$tovars[$value['id']][$val['id']][1]:"").'</td>';
			$stat_sh.='</tr>';
			// Фух... собрали ^_^
		}*/
		// INIT USERS
		$list_ur=Registry::get('db')->select("SELECT `id`,`name` FROM `users` ORDER BY `id` ASC");
		$users_html='';
		foreach ($list_ur as $value) $users_html.='<option value="'.$value['id'].'">'.$value['name'].'</option>';
		// END INIT USERS
		// INIT REGIONS
		$list_rg=Registry::get('db')->select("SELECT `id`,`name` FROM `regions`");
		$regions='';
		foreach ($list_rg as $value) $regions.='<option value="'.$value['id'].'">'.$value['name'].'</option>';
		$stat_reg='<tr align="center"><td>Имя</td>';
		$stat_reg_sql=Registry::get('db')->select("SELECT `u`.`id` as `uid`,`r`.`id` as `rid`,MAX(`lp`.`time_stop`) as `time`, COUNT(*)-COUNT(`lp`.`time_stop`) as `start`
										FROM `users` as `u` CROSS JOIN `regions` as `r` LEFT JOIN `log_parse` as `lp` ON `lp`.`user_id`=`u`.`id` AND `lp`.`region_id`=`r`.`id` 
										WHERE `lp`.`id` IS NOT NULL 
										GROUP BY `u`.`id`,`r`.`id`");
		$flag=array();
		foreach ($stat_reg_sql as $value)	$flag[$value['uid']][$value['rid']]=$value['start']>0 ? 0 : date("d.m H:i", $value['time']);
		foreach ($list_rg as $val)	$stat_reg.='<td>'.substr($val['name'], 0, 6).'</td>';
		$stat_reg.='</tr>';
		// END INIT REGIONS
		unset($img);
		// сборка матрицы для парсера цен
		foreach ($list_ur as $value) {
			$stat_reg.='<tr align="center"><td>'.$value['name'].'</td>';
			foreach ($list_rg as $val) {
				$stat_reg.='<td>';
				if (isset($flag[$value['id']][$val['id']])) {
					if ($flag[$value['id']][$val['id']]===0) {
						$img['img']='online.png';
						$img['title']='В процессе';
					}
					else {
						$img['img']='offline.png';
						$img['title']=$flag[$value['id']][$val['id']];
					}
				}
				else {
					$img['img']='offline.png';
					$img['title']='Не запускался!';
				}
				$stat_reg.='<img src="style/img/'.$img['img'].'" title="'.$img['title'].'" /> </td>';
			}
			$stat_reg.='</tr>';
		}
		// Вставка значений в TPL
		//$html=str_replace('#SHOPS#', $shops, $html);
		$html=str_replace('#USERS#', $users_html, $html);
		$html=str_replace('#REGIONS#', $regions, $html);
		$html=str_replace('#PLAT_PRICE#', $plat_price, $html);
		//$html=str_replace('#PLAT_RATE#', $plat_rate, $html);
		//$html=str_replace('#STAT_SH#', $stat_sh, $html);
		//$html=str_replace('#STAT_REG#', $stat_reg, $html);
		echo $html;
		exit ;
	}

	/**
	 * Logout - cleaning cookie
	 *
	 * @return self redirection
	 */
	public function logout() {
		setcookie('parse', null, 0, '/');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit ;
	}

	/**
	 * Parse file in to $list and call method for 1 row or for full parse
	 * @param string $cont - Link to file
	 */
	public function getFile($cont) {
		$cont=fopen($cont, "r");
		$rows=0;
		while ($list[$rows]=fgetcsv($cont, 1000, ";")) $rows++;
		fclose($cont);
		if ($rows==1) $this->parser_one_row($list);
	}
	
	/**
	 * Парсит CSV файла с разделителем ;
	 * @param string $cont пусть к файлу
	 * @return bool|array false при ошибке или массив со строками
	 */
	public function getCSV($cont) {
		if(!file_exists($cont))return false;
		$cont=fopen($cont, "r");
		$rows=0;
		while ($list[$rows]=fgetcsv($cont, 1000, ";")) $rows++;
		fclose($cont);
		if (count($list)>0) return $list;
		else return false;
	}
	
	/**
	 * Парсит csv или работает с массивом
	 * Выставляя первый элемент массива ключом.
	 * @param string|array путь до файла или сразу массив
	 * @return array массив с ключом
	 */
	public function getAscArray($array){
		if(is_string($array)) $array=$this->getCSV($array);
		if($array==false) return false;
		$result=Array();
		foreach ($array as $value) {
			if(is_bool($value[0]) || $value[0]=="")continue;
			$row=Array();
			foreach ($value as $key => $val){
				if($key==0)continue;
				$row[]=$val;
			}
			$result[$value[0]]=$row;
		}
		return $result;
	}
	
	/**
	 * Starting parser daemon with need parameters
	 * 
	 * @param enum(price,rate) $type тип прокси либо цены либо ставки
	 * @param string $file ссылка для файла (Нужна в базе для демона)
	 * @param int $platform_id id площядки для парса 
	 * @param string $proxy вид прокси
	 * 
	 * @return int возвращяет ID запущеного парсера
	 */
	protected function parser_daemon($type,$file, $platform_id, $proxy = null) {
		// Определение удаленного адреса
		$remote_addr=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:"127.0.0.1";
		// Определение хоста с которого запускают парсер
		$http_host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:"webtools.ru:80";
		// Установка флага для парсера (Искать по уже существующей базе или по Названию товара)
		$base=isset($_POST['base'])? 1 : (Registry::get('base')==null?0:1);
		// Проверка указания прокси
		if($proxy==null)$proxy = $_POST['proxy'];
		// Выбор типа парса и инициализация переменных с пользователем и регионом
		switch ($type) {
			case 'price':
				$user=$_SESSION['user'];
				$region=$_SESSION['region'];
				break;
			case 'rate':
				$user=$_SESSION['id'];
				$region=$_SESSION['shops'];
				break;
			default: echo "type not selected";exit;
		}
		// Занесение данных в базу в log_parse для демона и вытаскивание ID парсера
		$parse_id=Registry::get('db')->query("INSERT INTO `log_parse` (`user_id`,`region_id`,`type`,`platform_id`,`b_title`,`proxy`,`file`,`user_ip`) VALUES (?,?,?,?,?,?,?,?)", $user, $region, $type, $platform_id, $base, $proxy, $file, $remote_addr);
		// Формирование заголовков и вызов страницы демона. без ожидания ответа
		$header="GET ".PARS_URL."/parserd.php?parse_id={$parse_id} HTTP/1.0\r\n";
		$header.="Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/vnd.ms-excel, application/msword, */*\r\n";
		$header.="Accept-Language: ru\r\n";
		$header.="Content-Type: application/x-www-form-urlencoded\r\n";
		$header.="User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; (R1 1.5))\r\n";
		$header.="Host: ".$http_host."\r\n\r\n";
		// Пока не запустится демон (Пока он не пропишет в базу свой pid из консоли) продолжать вызов страницы
		while(Registry::get('db')->selectCell("SELECT `pid` FROM `log_parse` WHERE `id`=?",$parse_id)==0){
			$sckt=fsockopen($http_host, 80);
			fputs($sckt, $header);
			sleep(5);
			fclose($sckt);
		}
		$this->message.='Парсер успешно запущен! <button onclick="parse_stat()">Статус</button> <br/>';
		return $parse_id;
	}

	/**
	 * Checking file for existing and return string of existing with time or
	 * non-existing
	 * if param $file are null it takes $this->file string
	 *
	 * @param string $file - File path
	 * @return string
	 */
	public function chkFile($file=null) {
		if($file===null)$file=$this->file;
		if (file_exists($file))
			$result="Файл создан: ".date("d F H:i", filemtime($file))." (".count(file($file))." строк)";
		else
			$result="Файл отсутствует!";
		return $result;
	}

	/**
	 * Recursively delete log file in directory
	 */
	public function cache_clean() {
		$dir='pages/'.$_SESSION['region'].'/';
		$op_dir=opendir($dir);
		while ($file=readdir($op_dir))
			if ($file!="." && $file!="..")
				unlink($dir.$file);
		closedir($op_dir);
		$this->message.='Кэш '.$_SESSION['region'].' успешно очищен!<br />';
	}

	/**
	 * Download file from $file or $this->file if exist
	 *
	 * @param string $file - File path
	 * @param enum(csv,txt) $type расширение
	 * @return binary file or FALSE
	 */
	public function download($file=null, $type=false) {
		// Проверка типа файла и существование файла
		if($type==false){
			if ($file==null || !file_exists($file)) {
				if ($this->file==null || !file_exists($this->file))	return false;
				else $file=$this->file;				
			}
			else{
				// Определяем размер и имя файла
				$name=basename($file);
				$size=filesize($file);
			}
		}
		// Если тип массив о_0 (Не помню где такое мог использовать) наверно сделал на будующие
		elseif(is_array($type)) {
			$name=$type[0].".".$type[1];
			$result="";
			if($type[1]=='csv')$glue=';';
			elseif($type[1]=='txt')$glue='	';
			if(is_array($file)){
				foreach ($file as $value) {
					if(is_array($value)) $result.=join($glue,$value)."\r\n";
					else $result.=$value."\r\n";
				}
			}
			else $result=$file;
		}
		else return false;
		// Отпправка заголовков
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename='.$name);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		// Отправка заголовка c размером файла, если это файл а не текст.
		if(isset($size)) header('Content-Length: '.$size);
		ob_clean();
		flush();
		// Если отдаем файл
		if(isset($size)) readfile($file);
		// Если отдаем бинарные данные
		elseif(isset($result)) echo $result;
		exit;
	}

	/**
	 * Get all settings from base and define them
	 * Определение констант
	 */
	public static function constan() {
		$constant=Registry::get('db')->select("SELECT `name`,`value` FROM `settings`");
		foreach ($constant as $val) define($val['name'], $val['value']);
	}

	/**
	 * Get all settings from base and return them
	 * Вытаскивание списка констант для панели Настроек
	 */
	public static function getconstan() {
		$constant=Registry::get('db')->select("SELECT `name`,`value` FROM `settings`");
		return $constant;
	}
	
	/**
	 * Full Base Clean!!! save just Settings and tovar!
	 * @param string $key необходимое условие бесмертия базы iddqd
	 * @return string
	 */
	public function full_clean($key){
		if ($key=="iddqd"){
			Registry::get('db')->query("TRUNCATE TABLE `log_errors`;");
			Registry::get('db')->query("TRUNCATE TABLE `log_ip`;");
			Registry::get('db')->query("TRUNCATE TABLE `log_parse`;");
			Registry::get('db')->query("TRUNCATE TABLE `log_pid`;");
			Registry::get('db')->query("TRUNCATE TABLE `queryPrice`;");
			Registry::get('db')->query("TRUNCATE TABLE `queryData`;");
			Registry::get('db')->query("TRUNCATE TABLE `resultRate`;");
			Registry::get('db')->query("TRUNCATE TABLE `resultPrice`;");
			if (isset(Registry::get('db')->error)) $this->message.=print_r(Registry::get('db')->error);
			else $this->message.="tables are clean! Nightmare mode ON!";
		}
		else $this->message.="You don`t save? 0_o";	
	}
	
	/**
	 * Достает из базы список площадок
	 * @param int $flag возможность доставать только площядки по которым можно парсить цены
	 * @return array массив с площадками, ключ - id площадки
	 */
	public static function get_platforms($flag=0){
		$list=Array();
		foreach (Registry::get('db')->select('SELECT * FROM `platform` WHERE `price_flag`>=?',$flag) as $value) {
			$list[$value['id']]=Array('title'=>$value['title'],'encoding'=>$value['encoding']);
		}
		return $list;
	}
	
	/**
	 * Проверка существования площадки по ID и вытаскивание информации при существовании (странно, что не обьединил с функцией выше)
	 * @param int $platform id площадки
	 * @return bool|array Либо false либо информацию
	 */
	public static function chk_platform($platform){
		$data=Registry::get('db')->selectRow('SELECT * FROM `platform` WHERE `id`=?',(int) $platform);
		if(count($data)==0) return false;
		return $data;
	}
	
	/**
	 * Проверка существования выбранного региона.
	 * Ставилась на местах обезательного указия региона
	 * @param int $reg id региона (ID позаимствованны у yandex market)
	 * @return int id региона
	 */
	public static function chk_region($reg=null){
		if(!isset($reg) OR Registry::get('db')->selectCell('SELECT COUNT(*) FROM `regions` WHERE `id`=?',$reg)==0){
			$reg=Registry::get('db')->select("SELECT * FROM `regions`");
			$list="";
			foreach ($reg as $value) $list.='<option value="'.$value['id'].'">'.$value['name'].'</option>';		
			echo 'Выберите регион:
				<form action="" method="get">
				<select name="reg">'.$list.'</select><input type="submit" value="Продолжить" />
				</form>';
			exit;
		}
		else return $reg;
	}
	/**
	 * Вызов класса для обновления IP адресов
	 * 
	 * @return 
	 */
	public static function upload() {
		return new Upload();
	}
	
	/**
	 * Планировалось шифровать пароли от площадок этим методом.
	 * matrix encoding 
	 *
	 * @param string $input - encoding/decoding string
	 * @param bool $decrypt - false for encoding
	 *
	 * @return string - decoding/encoding string
	 */
	//@formatter:off
	public static function dsCrypt($input, $decrypt=false) {
		$o=$s1=$s2=array();
		// Arrays for: Output, Square1, Square2
		// формируем базовый массив с набором символов
		$basea=array('?','(','@',';','$','#',"]","&",'*');
		// base symbol set
		$basea=array_merge($basea, range('a', 'z'), range('A', 'Z'), range(0, 9));
		$basea=array_merge($basea, array('!',')','_','+','|','%','/','[','.',' '));
		$dimension=9;
		// of squares
		for ($i=0; $i<$dimension; $i++) {// create Squares
			for ($j=0; $j<$dimension; $j++) {
				$s1[$i][$j]=$basea[$i * $dimension + $j];
				$s2[$i][$j]=str_rot13($basea[($dimension * $dimension - 1) - ($i * $dimension + $j)]);
			}
		}
		unset($basea);
		$m=floor(strlen($input) / 2) * 2;
		// !strlen%2
		$symbl=$m==strlen($input) ? '' : $input[strlen($input) - 1];
		// last symbol (unpaired)
		$al=array();
		// crypt/uncrypt pairs of symbols
		for ($ii=0; $ii<$m; $ii+=2) {
			$symb1=$symbn1=strval($input[$ii]);
			$symb2=$symbn2=strval($input[$ii + 1]);
			$a1=$a2=array();
			for ($i=0; $i<$dimension; $i++) {// search symbols in Squares
				for ($j=0; $j<$dimension; $j++) {
					if ($decrypt) {
						if ($symb1===strval($s2[$i][$j]))
							$a1=array($i,$j);
						if ($symb2===strval($s1[$i][$j]))
							$a2=array($i,$j);
						if (!empty($symbl) && $symbl===strval($s2[$i][$j]))
							$al=array($i,$j);
					}
					else {
						if ($symb1===strval($s1[$i][$j]))
							$a1=array($i,$j);
						if ($symb2===strval($s2[$i][$j]))
							$a2=array($i,$j);
						if (!empty($symbl) && $symbl===strval($s1[$i][$j]))
							$al=array($i,$j);
					}
				}
			}
			if (sizeof($a1) && sizeof($a2)) {
				$symbn1=$decrypt ? $s1[$a1[0]][$a2[1]] : $s2[$a1[0]][$a2[1]];
				$symbn2=$decrypt ? $s2[$a2[0]][$a1[1]] : $s1[$a2[0]][$a1[1]];
			}
			$o[]=$symbn1.$symbn2;
		}
		if (!empty($symbl) && sizeof($al))// last symbol
			$o[]=$decrypt ? $s1[$al[1]][$al[0]] : $s2[$al[1]][$al[0]];
		return implode('', $o);
	}

}
?>