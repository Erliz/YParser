<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Парсер ЦЕН market.yandex.ru</title>
<script src="http://code.jquery.com/jquery-1.6.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
var stat_init = 0;
function parse_stat() {
	if (stat_init == 0){
		document.getElementById('message').innerHTML='';
	    var user_id = document.getElementById('user_id').innerHTML;
	    var shop_id = document.getElementById('shop_id').innerHTML;
    	stat_init = 1;
	    $.get('check.php',{parse: 'get_id', shop_id: shop_id, user_id: user_id}, function(res) {
	        if (typeof (res['parse_id']) !== 'undefined') {
		       		 var cycle = setInterval(function() {
			             $.get('check.php',{parse: 'get_stat',parse_id: res['parse_id']}, function(stat) {
			             	var time_start = new Date(stat['time_start'] * 1000);
			             	if (stat['time_stop'] === '0'){
			             		var time_stop = new Date(stat['time_stop'] * 1000);
			             		$.get('check.php',{parse: 'get_error_count',parse_id: res['parse_id']},function(error_count) {
			             			document.getElementById('message').innerHTML='ПАРСЕР ОСТАНОВЛЕН!<br/>'+'Парс начался: '+time_start.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>Было найдено: '+error_count+' ошибок.<br/><a href="">Обновите страницу!</a>';
			             			clearInterval(cycle);
			             		},'json');
		             		}
			             	else if (stat['time_stop'] > 0){
			             		var time_stop = new Date(stat['time_stop'] * 1000);
			             		$.get('check.php',{parse: 'get_error_count',parse_id: res['parse_id']},function(error_count) {
			             			document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+' и Закончился: '+time_stop.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>Было найдено: '+error_count+' ошибок.<br/><a href="">Обновите страницу!</a>';
			             			clearInterval(cycle);
			             		},'json');
		             		}
			             	else document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/><button onclick="parse_stop('+res['parse_id']+');">STOP</button> ';
			             },'json')
			         },10000);
	        }
	        else document.getElementById('message').innerHTML=res['error'];
	    },'json');
    }
}
function parse_stop(id){
	$.get('check.php',{parse: 'set_stop', parse_id: id}, function(res) {
		alert('Парсер Остановлен!');
	},'json');
}
</script>
</head>
<body>
	<span style="display:block;margin-left:auto;margin-right:auto;font-size:22px;color:purple;" onclick="parse_stat();">ЦЕНЫ</span><br/> 
	
	<span style="display: none;" id="shop_id">#SHOP_ID#</span><span style="display: none;" id="user_id">#USER_ID#</span>
	
	<div>
		<span style="color:#FF3300; font-size:large; font-weight:200;" id="message">#MESSAGE#</span>
	</div>
	<br/>
	<div style="margin-left:auto;margin-right:auto">
	Добро пожаловать, <strong>#USER#</strong> в Парсер <strong>#PLATFORM#</strong> региона <strong>#REGION#</strong><br/><br/>
	<a href="files/example_price.csv">ОБРАЗЕЦ</a><br/><br/>
	Файл вида: CSV, UTF-8(кодировка):</br>Код(Опт); Название; Цена1(себест); Цена2(мвиц); Цена3(моиц);</br></br>
	<form action="" method="post" enctype="multipart/form-data">
		<span>Тип прокси:</span><br/>
		<input type="radio" name="proxy" id="proxy_s" value="static" checked="checked"/><label for="proxy_s">Наши</label>
		<br/>
		<input type="radio" name="proxy" id="proxy_d" value="dynamic" /><label for="proxy_d">Покупные</label>
		<!--<br/><br/>
		<span>Использовать ID:</span><br/>
		<input type="checkbox" name="base" id="base_e" value="eurobit" /><label for="base_e">Eurobit</label>   disabled="disabled"-->
		<br/><br/>
		<input type="file" name="upload" /><br/>
		<input type="submit" value="Запустить Парсер!" name="parse" />
	</form>
	</div>
	<form action="" method="POST" name="download">
		<br/><br/>
		<input type="checkbox" name="midPrice" id="midPrice" value="true"/><label for="midPrice">Средняя цена</label><br/>
		<input type="radio" name="type" id="type_price" checked="checked" value="price" /><label for="type_price">Price</label><br/>
		<input type="radio" name="type" id="type_mvic" value="mvic" /><label for="type_mvic">МВИЦ</label><br/>
		<input type="radio" name="type" id="type_moic" value="moic" /><label for="type_moic">МОИЦ</label>
		<br/>
		#SHOPS_PRICE#
		<!-- <input type="radio" name="type" id="type_yandexData" value="yandexData" /><label for="type_yandexData">yandexData</label> 
		<br/>
		
		<br/>
		<input type="radio" name="type" id="type_csv" value="Characteristics" /><label for="type_csv">Характеристики</label>-->
		<br/>
		<input type="submit" name="download" value="Сформировать и Скачать" #FILE_GEN_DIS# />
	</form>
	<br />
	<form action="" method="post" enctype="multipart/form-data">
		<input type="file" name="tovar_check" /><br/>
		<input type="submit" value="Проверить товары в базе" name="tovar_check" />
	</form>
	<br />
	<form action="" method="POST">
		<input type="submit" onClick="confirm(\'Уверены, что хотите очистить базу???\')" value="Очистить Таблицу" #FILE_GEN_DIS# name="TRUNCATE"/>
	</form>
	<form action="" method="POST">
		<input type="submit" onClick="confirm(\'Уверены, что хотите очистить кэш???\')" value="Очистить Кэш" #CASH_CLEAN# name="Cache"/>
	</form>
	<br/>
	<a style="color:#CCC; font-size:12px;" href="?logout=now">Выход</a>
</body>
<html>