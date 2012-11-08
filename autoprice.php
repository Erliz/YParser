<?php
/**
 * Скрипт запускающий автопарсер цен
 */
// инициализация 
session_name('parse');
session_start();
set_time_limit(3600);
require_once ("autoload.php");
file_put_contents('pages/'.getmypid()."_price.pid", ' ');
Registry::set('db', Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');
Face::constan();

// Проверка на существование региона
if(!isset($_GET['reg']) OR Registry::get('db')->selectCell('SELECT COUNT(*) FROM `regions` WHERE `id`=?',$_GET['reg'])==0){
	$reg=Registry::get('db')->select("SELECT * FROM `regions`");
	$list="";
	foreach ($reg as $value) $list.='<option value="'.$value['id'].'">'.$value['name'].'</option>';		
	echo 'Выберите регион:
		<form action="" method="get">
		<select name="reg">'.$list.'</select><input type="submit" value="Продолжить" />
		</form>';
	exit;
}
// инициализация данных
$region=$_GET['reg'];
$user='99';
$file_auto='files/autoprice_'.$region.'.txt';
Registry::set('base',1);
$_SESSION['user']=$user;
$_SESSION['region']=$region;
$_SESSION['platform']=1;

/**
 * получение данных по магазинам в регионе
 * 
 * @param array $shops список ID магазинов
 * @param int $reg ID региона
 */
function getShops(array $shops, $reg){
	return Registry::get('db')->select("SELECT * FROM `shop_id` WHERE `region`=? AND `shop_id` IN (?a)",$reg,$shops);
}

/**
 * Получени и запись цен товара в файл
 * Себестоимость, МВИЦ, МОИЦ
 * 
 * @param string $file_auto путь к файлу
 * @param int $region ID региона
 */
function sv_tovar($file_auto,$region){
	$interface=new FaceAuto();
	// получение кода региона SV
	$sv_reg=Registry::get('db')->selectCell("SELECT `sv_reg` FROM `regions` WHERE `id`=?",$region);
	// соединение с базой
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!') 
	      or die ("Can't connect to Microsoft SQL Server");
	mssql_select_db('uchet', $conn) or die ("Can't select databes");
	// вызов процедуры
	$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	// обработка ошибки SV
	if(mssql_get_last_message()!=="Changed database context to 'uchet'." && !strpos(mssql_get_last_message(), "deadlock")) {echo "</br><b>MSSQL ERROR:</b>".mssql_get_last_message();exit;}
	while (strpos(mssql_get_last_message(), "deadlock")) {
		echo mssql_get_last_message();
		sleep(300);
		$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	}
	// Получение уникальных товаров
	$proof=$interface->tovarlist_price();
	// кондеи
	$union=$interface->unionlist();
	// цены на ручной себестоимости
	$exlusive=$interface->getAscArray('files/autoprice_'.$region.'.csv');
	// очистка таблицы с незанесёнными кодами, которые уже есть в базе
	Registry::get('db')->query("DELETE FROM `dev_parser`.`dataTovar_trash` WHERE `id` IN (?a)", $proof);
	// проверка и парс файла
	if(file_exists($file_auto))unlink($file_auto); 
	if(is_bool($result)) exit;
	for ($i = 0; $i < mssql_num_rows($result); ++$i){
		$line = mssql_fetch_assoc($result);
		$tovars[$line['code']]=Array((int)$line['count'],(int)ceil($line['mpBp']),(int)ceil($line['mvic']),(int)ceil($line['moic']));
	}
	$handle=fopen($file_auto, 'a');
	// формирование файла
	foreach ($tovars as $key => $value) {
		if(in_array($key, $proof)) {
			$price=$value[1];
			$mvic=$value[2];
			$moic=$value[3];
			$union_id=array_search($key,$union[0]);
			if($union_id!==false) {
				$close=true;
				if(isset($tovars[$union[1][$union_id]][1])){
					$price+=$tovars[$union[1][$union_id]][1];
					$mvic+=@$tovars[$union[1][$union_id]][2];
					$moic+=@$tovars[$union[1][$union_id]][3];
				}
				else{
					unset($union_id);
					continue;
				}
			}
			if(isset($exlusive[$key])){
				 $price=$exlusive[$key][1];
			 }
			fputcsv($handle, Array($key,'',ceil($price),ceil($mvic),ceil($moic)),';');
			unset($union_id);
		}
		elseif (in_array($key, $union[1]))continue;
		// добавление неивестного нового товара
		else Registry::get('db')->query("REPLACE INTO `dev_parser`.`dataTovar_trash` (`id`) VALUE (?)",$key);
	}
	fclose($handle);
	return true;
}

/**
 * Заливка выставленных цен в SV
 * 
 * @param int $shop_id ID магазина
 * @param array $list 
 */
function sv($shop_id,$list){
	// файл
	$fp='/mnt/dc-sql3/BulkInsert/priceInet.txt';
	$rows=Array();
	$report=Array();
	// формирование массива для заливки в файл
	foreach ($list as $v)$rows[]=array($shop_id,$v[0],$v[1]);
	unset($list);
	// очистка файла
	if(file_exists($fp)) unlink ($fp);
	// запись в файл
	$handle=fopen($fp, "a");
	foreach ($rows as $row)	fputcsv($handle, $row, '	');
	fclose($handle);
	// навешивание украшательств на строки
	$convert=file_get_contents($fp);
	$convert=str_replace("\n", "\r\n", $convert);
	file_put_contents($fp, $convert);
	// коннект к базе
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!'); 
    if(!isset($conn)) echo "Can't connect to Microsoft SQL Server!";
	mssql_select_db('uchet', $conn);
	// выполнение процедуры
	$result = @mssql_query("EXEC cllc_SetPricesFromParser");
	// обработка тормозов SV
	if(mssql_get_last_message()!=="Changed database context to 'uchet'." && !strpos(mssql_get_last_message(), "deadlock")) {echo "</br><b>MSSQL ERROR:</b>".mssql_get_last_message();exit;}
	while (strpos(mssql_get_last_message(), "deadlock")) {
		echo mssql_get_last_message();		
		sleep(300);		
		$result = @mssql_query("EXEC cllc_SetPricesFromParser");
	}
	// обработка полученных результатов
	if(isset($result) ){
		$report=Array();
		while($line = mssql_fetch_assoc($result)) $report[$line['tovar']]=$line;
		if (count($report) == 0) {
			$answer = true;
		}
		else $answer = $report;
	}
	else {
		echo "mssql_query fail!!!!!!";
		$answer=false;
	}
	mssql_close();
	return $answer;
}

/**
 * Заливка 10ти цен конкурентов
 * @param $list
 *
 * @return array|bool
 */
function sv_10($list){
	$fp='/mnt/dc-sql3/BulkInsert/priceRivalInet.txt';
	$rows=Array();
	$report=Array();
	$upload=Array();
	foreach ($list as $key=> $value) {
		foreach ($value as $k => $v) {
			$rows[] = array($k + 1, $key, $v[0], iconv("UTF-8", "CP1251", mb_substr($v[1], 0, 15, "UTF-8")));
		}
	}
	unset($list);
	if(file_exists($fp)) unlink ($fp);
	$handle=fopen($fp, "a");
	foreach ($rows as $row)	fputcsv($handle, $row, '	');
	fclose($handle);
	$convert=file_get_contents($fp);
	$convert=str_replace("\n", "\r\n", $convert);
	$convert=str_replace("\"", "", $convert);
	file_put_contents($fp, $convert);
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!');
	if(!isset($conn)) echo "Can't connect to Microsoft SQL Server!";
	mssql_select_db('uchet', $conn);
	$result = @mssql_query("EXEC cllc_SetPricesRivalFromParser @upd_date='".date("Ymd",time())."'");
	while (strpos(mssql_get_last_message(), "deadlock")) {
		sleep(30);
		$result = @mssql_query("EXEC cllc_SetPricesRivalFromParser @upd_date='".date("Ymd",time())."'");
	}
	if($_GET['debug']=='true')echo mssql_get_last_message()."<br />";
	if(isset($result) ){
		$report=Array();
		while($line = mssql_fetch_assoc($result)) $report[$line['tovar']]=$line['value'];
		if (count($report) == 0) {
			$answer = true;
		}
		else $answer = $report;
	}
	else {
		echo "mssql_query fail!!!!!!";
		$answer=false;
	}
	mssql_close();
	return $answer;
}
// получение списка магазинов
$shops=getShops(Array('2','1','4','5','6','7'),$region);
// инициализация интерфейса
$interface=new FacePrice();
//cleaning!
echo "Очистка запущена!<br/>";
$shop=Array();
$dir='pages/'.$region.'/';
$op_dir=opendir($dir);
if($op_dir){
	while ($file=readdir($op_dir))
		if ($file!="." && $file!="..")
			unlink($dir.$file);
	closedir($op_dir);
}
else echo "Нет кэша для региона ".$region;
Registry::get('db')->query("DELETE FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?", $user, $region,$_SESSION['platform']);
echo "Очистка завершена!<br/>";
//end

//getting price
echo "Получение цен!";
sv_tovar($file_auto,$region);
echo "Цены записаны в ".$file_auto."<br/>";
//end
//parser start
echo "Парсер запущен!<br/>";
//if($region=='213')$proxy='static';
//else
$proxy='dynamic';
$parser_id=$interface->start_daemon($file_auto,$proxy);
while (Registry::get('db')->selectCell("SELECT `time_stop` FROM `log_parse` WHERE `id`=?",$parser_id)===null) sleep(60);
echo "Парсер завершен!<br/>";
//end
//FaceAuto::dif_mic($user, $region,'mvic');
//FaceAuto::dif_mic($user, $region,'moic');
//FaceAuto::report($user, $region);
//FaceAuto::price_file($user, $region);
Download::price_file(true);
echo "Обновление себестоимости запущена!<br/>";
sv_tovar($file_auto,$region);
echo "Цены перезаписанны в ".$file_auto."<br/>";
$handle=fopen($file_auto, "r");
while ($row=fgetcsv($handle, 1000, ";")){
	if(!preg_match('/[0-9]+/', $row[0])) continue;
	$p_tovar=$row[0];
	if($row[2]=='') continue;
	else $p_mpi=$row[2];
	$p_mvic = ($row[3]=='')?0:$row[3];
	$p_moic = ($row[4]=='')?0:$row[4]; 
	Registry::get('db')->query("UPDATE `resultPrice` 
	SET `mvic`=?,`moic`=?,`MPI`=? 
	WHERE `user_id`=? AND `platform_id`=? AND `region_id`=? AND `tovar_id`=? LIMIT 1;",$p_mvic,$p_moic,$p_mpi,$user,$_SESSION['platform'],$region,$p_tovar);
}
fclose($handle);
echo "Обновление себестоимости для Закончена!<br/>";
if($_SESSION['region']==213){
	#### Конкуренты 10 ####
	$query_10 = Registry::get('db')->select("SELECT `tovar_id`,`ten_shop` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?",$_SESSION['user'],$region, $_SESSION['platform']);
	$our_10=Registry::get('db')->selectCol("SELECT `name` FROM `shops`");

	$list_10=Array();
	$file_name_10='files/download/'.$region.'_top10.csv';
	if(file_exists($file_name_10))unlink($file_name_10);
	$handle_10=fopen($file_name_10, 'a');
	foreach ($query_10 as $value) {
		if(strlen($value['ten_shop'])<5) continue;
		$top_10=Array();
		$top_10=explode('+',$value['ten_shop']);
		foreach ($top_10 as $k => $v) {
			$shop_10=explode("|", $v);
			foreach ($our_10 as $sh_name) if(stristr($shop_10[2],$sh_name)) continue(2);
			$list_10[$value['tovar_id']][]=Array($shop_10[0],$shop_10[2]);
			fputcsv($handle_10, Array($value['tovar_id'],$shop_10[0],$shop_10[2]),';');
		}
	}
	fclose($handle_10);
	echo "Генерация завершена!<br/>";
	echo "Загрузка в SV!<br/>";
	$sv_10=sv_10($list_10);
	if ($sv_10 === true) {
		echo "Загрузка прошла успешно!<br/>";
	}
	elseif ($sv_10 === false) echo "Загрузка не выполнена!<br/>";
	else {
		echo "Загрузка выполнена не полностью!<br/>";
		$h = fopen('files/history/' . date("d_m_Y H:i", time()) . "." . $region . '_top10-errors.txt', "a");
		foreach ($sv_10 as $row) fputcsv($h, $row, '	');
		fclose($h);
		echo 'Отчёт создан! <a href="files/history/' . date("d_m_Y H:i", time()) . "." . $region . '_top10-errors.txt" >ОТЧЁТ</a><br/>';
	}
	#########
}
		
foreach ($shops as $value) {
	$convert=Array();
		echo "Генерация для {$value['sv_login']} запущена!<br/>";
	$list=Download::price_shop($value['shop_id'],false);
	if(!is_array($list)){
		echo "<b>Error:</b> Генерация для {$value['sv_login']} ОСТАНОВЛЕННА! неверный ответ скрипта Download::price_shop<br/>";
		var_dump($list);
		continue;
	}
	array_shift($list);
		echo "Генерация для {$value['sv_login']} завершена!<br/>";
	$file_name='files/download/'.$region.'_price_'.$value['sv_login'].'.txt';
	foreach (array('Код','Цена','Кластер') as $v) $convert[0][]=iconv("UTF-8", "CP1251", $v);
		echo "Загрузка в SV!<br/>";
	$list_sh=$list;	
	$stop=Registry::get('db')->selectCol("SELECT `id` FROM `dataTovar_stop` WHERE `shop_id`=?",$value['id']);
	foreach ($list_sh as $key => $v)
		if(in_array($v[0],$stop))
			unset($list_sh[$key]);
	sort($list_sh);
	reset($list_sh);
	if(isset($value['sv_id']) && $value['sv_id']!=0 && $value['sv_id']!=""){
		$sv=sv($value['sv_id'],$list_sh);
		if($sv===true) echo "Загрузка прошла успешно!<br/>";
		elseif($sv===false) echo "Загрузка не выполнена!<br/>";
		else{
				echo "Загрузка выполнена не полностью!<br/>";
			$h=fopen('files/history/'.date("d_m_Y H:i",time()).".".$region.'_price-errors_'.$value['sv_login'].'.txt', "a");
			foreach ($sv as $row) fputcsv($h, $row, '	');
			fclose($h);
				echo 'Отчёт создан! <a href="files/history/'.date("d_m_Y H:i",time()).".".$region.'_price-errors_'.$value['sv_login'].'.txt" >ОТЧЁТ</a><br/>';
		}
	}
		echo "Преобразование файла запущено!<br/>";
	foreach ($list_sh as $v) if($v[0]!=false) $convert[]=array($v[0],$v[1],$region);
	if (file_exists($file_name)) unlink($file_name);
	$handle=fopen($file_name, "a");
	foreach ($convert as $row) is_array($row)?fputcsv($handle, $row, '	'):var_dump($row);
	fclose($handle);
	unset($list_sh);
	$convert=file_get_contents($file_name);
	$convert=str_replace('"', '', $convert);
	$convert=str_replace("\n", "\r\n", $convert);
	file_put_contents($file_name, $convert);
		echo "Преобразование файла завершено!<br/>";
	rename($file_name, 'files/history/'.date("d_m_Y H:i",time())."_".$region.'_price_'.$value['sv_login'].'.txt');
}
unlink('pages/'.getmypid()."_price.pid");
?>