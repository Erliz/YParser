<?php
/**
 * Скрипт выполняет парс по аналогии с обычным парсом цен яндекс маркета. 
 * Но для определённого файла, 2 раза в неделю и заносит полученные данные по 10 магазинам в цене и наличии в SV
 */
session_name('parse');
session_start();
set_time_limit(3600);
require_once ("autoload.php");
file_put_contents('pages/'.getmypid()."_ten10.pid", ' ');
Registry::set('db', Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');
Face::constan();
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

$region=$_GET['reg'];
$user='98';
$file_auto='files/autotop10_'.$region.'.csv';
Registry::set('base',1);
$_SESSION['user']=$user;
//$_SESSION['id']=$region;
$_SESSION['platform']=1;
$_SESSION['region']=$region;
function getShops(array $shops, $reg){
	return Registry::get('db')->select("SELECT * FROM `shop_id` WHERE region=? AND `shop_id` IN (?a)",$reg,$shops);
}

function sv($list){
	$fp='/mnt/dc-sql3/BulkInsert/priceRivalInet.txt';
	$rows=Array();
	$report=Array();
	$upload=Array();
	foreach ($list as $key=>$value)
		foreach ($value as $k => $v)
			$rows[]=array($k+1,$key,$v[0],iconv("UTF-8","CP1251", mb_substr($v[1],0,15,"UTF-8")));
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
		if(count($report)==0) $answer=true;
		else $answer=$report;
	}
	else {
		echo "mssql_query fail!!!!!!";
		$answer=false;
	}
	mssql_close();
	return $answer;
}

echo "Очистка!<br/>";
$interface=new FacePrice();
Registry::get('db')->query("DELETE FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?", $user, $region, $_SESSION['platform']);
echo "Очистка завершена!<br/>";
//end
echo "Парсер запущен!<br/>";

$parser_id=$interface->start_daemon($file_auto,'dynamic');
while (Registry::get('db')->selectCell("SELECT `time_stop` FROM `log_parse` WHERE `id`=?",$parser_id)===null) sleep(60);
echo "Парсер завершен!<br/>";

echo "Очистка брака завершена!<br/>";
Registry::get('db')->query("DELETE FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=? AND five_shop!='' AND ten_shop=''", $user, $region, $_SESSION['platform']);
echo "Очистка брака завершена!<br/>";
echo "Парсер second запущен!<br/>";
$parser_id=$interface->start_daemon($file_auto,'dynamic');
while (Registry::get('db')->selectCell("SELECT `time_stop` FROM `log_parse` WHERE `id`=?",$parser_id)===null) sleep(60);
echo "Парсер завершен!<br/>";

$query = Registry::get('db')->select("SELECT `tovar_id`,`ten_shop` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?",$_SESSION['user'],$region, $_SESSION['platform']);
$our=Registry::get('db')->selectCol("SELECT `name` FROM `shops`");
		
$list=Array();
$file_name='files/download/'.$region.'_top10.csv';
if(file_exists($file_name))unlink($file_name);
$handle=fopen($file_name, 'a');
foreach ($query as $value) {
	if(strlen($value['ten_shop'])<5) continue;
	$top=Array();
	$top=explode('+',$value['ten_shop']);
	foreach ($top as $k => $v) {
		$shop=explode("|", $v);
		foreach ($our as $sh_name) if(stristr($shop[2],$sh_name)) continue(2);
		$list[$value['tovar_id']][]=Array($shop[0],$shop[2]);
		fputcsv($handle, Array($value['tovar_id'],$shop[0],$shop[2]),';');
	}
}
fclose($handle);
	echo "Генерация завершена!<br/>";
	echo "Загрузка в SV!<br/>";
$sv=sv($list);
if($sv===true) echo "Загрузка прошла успешно!<br/>";
elseif($sv===false) echo "Загрузка не выполнена!<br/>";
else{
		echo "Загрузка выполнена не полностью!<br/>";
	$h=fopen('files/history/'.date("d_m_Y H:i",time()).".".$region.'_top10-errors.txt', "a");
	foreach ($sv as $row) fputcsv($h, $row, '	');
	fclose($h);
		echo 'Отчёт создан! <a href="files/history/'.date("d_m_Y H:i",time()).".".$region.'_top10-errors.txt" >ОТЧЁТ</a><br/>';
}
unlink('pages/'.getmypid()."_ten10.pid");
?>