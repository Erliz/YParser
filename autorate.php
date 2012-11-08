<?php
/**
 * Скрипт запускающий автопарсер ставок
 */
session_name('parse');
session_start();
set_time_limit(3600);
require_once ("autoload.php");
file_put_contents('pages/'.getmypid()."_rate.pid", ' ');
Registry::set('db', Simple::createConnection());
Face::constan();

/**
 * Получени мвицовых позиций и обновление базы с группами товаров
 * 
 * @param int $region ID региона
 */
function get_mvic($region){
	$interface=new FaceAuto();
	$sv_reg=Registry::get('db')->selectCell("SELECT `sv_reg` FROM `regions` WHERE `id`=?",$region);
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!') 
	      or die ("Can't connect to Microsoft SQL Server");
	mssql_select_db('uchet', $conn) or die ("Can't select databes");
	$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	if(mssql_get_last_message()!=="Changed database context to 'uchet'." && !strpos(mssql_get_last_message(), "deadlock")) {echo "</br><b>MSSQL ERROR:</b>".mssql_get_last_message();exit;}
	while (strpos(mssql_get_last_message(), "deadlock")) {
		echo mssql_get_last_message();
		sleep(300);
		$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	}
	$proof=$interface->tovarlist_price();
	$union=$interface->unionlist();
	$exlusive=$interface->getAscArray('files/autoprice_'.$region.'.csv');
	
	Registry::get('db')->query("DELETE FROM `dev_parser`.`dataTovar_trash` WHERE `id` IN (?a)", $proof);
	if(is_bool($result)) exit;
	for ($i = 0; $i < mssql_num_rows($result); ++$i){
		$line = mssql_fetch_assoc($result);
		$tovars[$line['code']]=Array((int)$line['count'],(int)ceil($line['mpBp']),(int)ceil($line['mvic']),(int)ceil($line['moic']));
	}
	$result=Array();
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
			if(isset($exlusive[$key]) AND $price<$exlusive[$key][1]) $price=$exlusive[$key][1];
			if($mvic>0) $result[]=$key;
			unset($union_id);
		}
		elseif (in_array($key, $union[1]))continue;
		else Registry::get('db')->query("REPLACE INTO `dev_parser`.`dataTovar_trash` (`id`) VALUE (?)",$key);
	}
	return $result;
}

/**
 * Обновление цены товара из последнего файла по ценам
 * 
 * @param int $shop_id
 * @param int $platform_id
 * 
 * @return bool
 */
function get_price($shop_id,$platform_id=1){
	$tovars=Array();
	$shop=Registry::get('db')->selectRow("SELECT `region`,`sv_login` FROM `shop_id` WHERE `id`=?",$shop_id);
	$date=getdate(time());
	$name='files/history/'.($date['mday']+1).'_'.$date['mon'].'_'.$date['year'].' *:*_'.$shop['region'].'_price_'.$shop['sv_login'].'.txt';
	$files=glob($name);
	if(count($files)==0) return false;
	$last=array_pop($files);
	if(!is_file($last)) return false;
	$handle=fopen($last, 'r');
	while($row=fgetcsv($handle,1000,'	')){
		if(count($row)!=3 || !preg_match('/[0-9]+/',$row[0])) continue;
		Registry::get('db')->query("UPDATE `tovar_price` SET `price`=? WHERE `shop_id`=? AND `opt_id`=?",$row[1],$shop_id,$row[0]);
	}
	return true;
}

/**
 * Запись ставок в SV для электробурга
 * 
 * @param int $shop ID магазина
 * @param array $list массив с данными
 */
function sv($shop,$list){
	$fp='/mnt/dc-sql3/BulkInsert/rateInet.txt';
	$rows=Array();
	$report=Array();
	foreach ($list as $v) {
		$rows[]=array($v[0],2,$shop,$v[1]);
		$rows[]=array($v[0],1,$shop,$v[2]);
	}
	unset($list);
	if(file_exists($fp)) unlink ($fp);
	$handle=fopen($fp, "a");
	foreach ($rows as $row)	fputcsv($handle, $row, '	');
	fclose($handle);
	$convert=file_get_contents($fp);
	$convert=str_replace("\n", "\r\n", $convert);
	file_put_contents($fp, $convert);
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!'); 
    if(!isset($conn)) echo "Can't connect to Microsoft SQL Server!";
	mssql_select_db('InetRepl', $conn);
	$result = @mssql_query("DELETE FROM ParamValue WHERE ParamNameID IN (1,2) and UserName='".$shop."'");
	$result = @mssql_query("EXEC SetParamFromParser @fileName='rateInet.txt'");
	if(mssql_get_last_message()!=="Changed database context to 'InetRepl'." && !strpos(mssql_get_last_message(), "deadlock")) {echo "</br><b>MSSQL ERROR:</b>".mssql_get_last_message();exit;}
	while (strpos(mssql_get_last_message(), "deadlock")) {
		echo mssql_get_last_message();
		sleep(300);
		$result = @mssql_query("EXEC SetParamFromParser @fileName='rateInet.txt'");
	}
	if(isset($result) ){
		//echo "<table><tr><td>upcode</td><td>code</td><td>value</td><td>valuta</td><td>tovar</td><td>ForGroup</td></tr>";
		while($line = mssql_fetch_assoc($result)) {
			$report[$line['NomenclID']]=$line['ParamNameID'];
			//echo '<tr><td>'.$line['upcode'].'</td><td>'.$line['code'].'</td><td>'.$line['value'].'</td><td>'.iconv("CP1251", "UTF-8", $line['valuta']).'</td><td>'.$line['tovar'].'</td><td>'.$line['ForGroup'].'</td></tr>';
		}
		//echo "</table>";
		if(count($report)>0) return $report;
		else return true;
	}
	else {
		echo "mssql_query fail!!!!!!";
		return false;
	}
}

// Обновляем список МВИЦ позиций
Registry::get('db')->select("UPDATE `tovar` SET `tovar_group_id`=1 WHERE `tovar_group_id`=3");
Registry::get('db')->select("UPDATE `tovar` SET `tovar_group_id`=3 WHERE `tovar_id` IN (?a) AND `tovar_group_id`=1",get_mvic(213));

$_SESSION['platform']=1;
$interface=new FaceRate();
// массив с данными по магазинам
$shops=Array(/*'eurobit'=>Array('passwd'=>'GhKL84Hn34s',
								'user_id'=>'1',
								'host'=>'eurobit.ru',
								'ftp_login'=>'u_htload',
								'ftp_passwd'=>'6ouRMuve',
								'ids'=>Array('1'=>'000.txt','2'=>'001.txt','15'=>'038.txt')));//,'14'=>'015.txt'*/
		'electroburg'=>Array('passwd'=>'eb123burg',
								'user_id'=>'2',
								'host'=>'electroburg',
								'ftp_login'=>'',
								'ftp_passwd'=>'',
								'ids'=>array('3'=>'electroburg','4'=>'electroburgP','6'=>'electroburgN')));//'1069622'=>'electroburgK',
			
							/*	'moskvabyt'=>Array('passwd'=>'mskvbt',
								'user_id'=>'6',
								'host'=>'moskvabyt.ru',
								'ftp_login'=>'u192553',
								'ftp_passwd'=>'5plaramiseg2',
								'ids'=>array('1001677'=>'020.txt','1075081'=>'019.txt')),*/

//cleaning!
echo "Очистка запущена!<br/>";
foreach ($shops as $value) {
	foreach ($value['ids'] as $key=>$val) {
		$auth=Registry::get('db')->selectCell("SELECT `shops_id` FROM `platform_acc` as `pa` LEFT JOIN `platform_shops` as `ps` ON `ps`.`platform_acc_id`=`pa`.`id` WHERE `pa`.`platform_id`=? AND `ps`.`shop_id`=? LIMIT 1",$_SESSION['platform'],$key);
		Registry::get('db')->query('DELETE FROM `resultRate` WHERE `shop_id`=? AND `platform_id`=?', $key, $_SESSION['platform']);
		$dir='pages/'.$auth.'/';
		if(!file_exists($dir)) continue;
		$op_dir=opendir($dir);
		while ($file=readdir($op_dir))
			if ($file!="." && $file!="..")
				unlink($dir.$file);
		closedir($op_dir);
	}
}
echo "Очистка завершена!<br/>";
//end
foreach ($shops as $key => $value) {
	echo "Магазин: ".$key."<br/>";
	//$_SESSION['pass']=$value['passwd'];
	foreach ($value['ids'] as $k => $val) {
		echo "<br/>ID: ".$k."<br/>";
		$_SESSION['id']=$k;
		get_price($k,$_SESSION['platform']);
		$_SESSION['shops']=Registry::get('db')->selectCell("SELECT `shops_id` FROM `platform_acc` as `pa` LEFT JOIN `platform_shops` as `ps` ON `ps`.`platform_acc_id`=`pa`.`id` WHERE `pa`.`platform_id`=? AND `ps`.`shop_id`=? LIMIT 1",$_SESSION['platform'],$k);
		
		echo "Парсер запущен!<br/>";
		$parser_id=$interface->start_daemon('files/autorate.csv','static');
		while (Registry::get('db')->selectCell("SELECT `time_stop` FROM `log_parse` WHERE `id`=?",$parser_id)===null) sleep(60);
		echo "Парсер завершен!<br/>";
		echo "Генерация запущена!<br/>";
		new RateGenerate();
		echo "Генерация завершена!<br/>";
		
		if(isset($value['host']) && file_exists('files/download/'.$_SESSION['id'].'_'.$_SESSION['platform'].'_rate.csv')){
			echo "Преобразование файла запущено!<br/>";
			$handle=fopen('files/download/'.$_SESSION['id'].'_'.$_SESSION['platform'].'_rate.csv', "r");
			$list=Array();
			$rows=0;
			while ($list[$rows]=fgetcsv($handle, 1000, ";")) $rows++;
			fclose($handle);
			if(file_exists('files/download/'.$val))unlink('files/download/'.$val);
			$handle=fopen('files/download/'.$val, "a");
			if($key=="eurobit") $cluster=explode(".",$val);
			else $cluster[0]=$val;
			$header=array('Код','маркет bid','маркет cbid','Кластер');
			foreach ($header as $pos => $v) $header[$pos]=iconv("UTF-8", "CP1251", $v);
			fputcsv($handle, $header,'	');
			$list_sv=Array();
			foreach ($list as $v) if($v[0]!=false){
				$list_sv[]=Array($v[0],'1',$v[1]);
				fputcsv($handle, array($v[0],'1',$v[1],$cluster[0]),'	');//round($v[1]-($v[1]*0.3))
			}
			unset($list);
			fclose($handle);
			// Для Электробурга!
			if($key=="electroburg") sv($val, $list_sv);
			$convert=file_get_contents('files/download/'.$val);
			$convert=str_replace('"', '', $convert);
			$convert=str_replace("\n", "\r\n", $convert);
			file_put_contents('files/download/'.$val, $convert);
			echo "Преобразование файла завершено!<br/>";
			$num=0;
			if($key=="eurobit"){
				while(($connect = ftp_connect($value['host']))==false) {
					$num++;
					sleep (2);
					if($num==10) {
						echo "Cоединение с ".$value['host'].' не установленно! Загрузите файл в ручную: <a href="files/download/'.$val.'">'.$val.'</a><br\><br\>';
						continue(2);
					}
				}
				echo "Соединение с ".$value['host'].' установленно!';
				ftp_login($connect, $value['ftp_login'], $value['ftp_passwd']);
				while(ftp_size($connect, $val)!='-1') sleep (30);
				ftp_put($connect, ($value['user_id']==6)? "moskvabyt.ru/www/tload/".$val:$val, 'files/download/'.$val, FTP_ASCII);
				echo "Файл с ".$val.' загружен!';
				ftp_close($connect);
			}
			rename('files/download/'.$val, 'files/history/'.date("d_m_Y H:i",time()).".".$cluster[0].'_rate_'.$value['host'].'.txt');
		}
	}
}
unlink('pages/'.getmypid()."_rate.pid");
?>