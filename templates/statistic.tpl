<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Парсер Статистики площадок</title>
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
			             	if (stat['time_stop'] > 0){
			             		var time_stop = new Date(stat['time_stop'] * 1000);
			             		$.get('check.php',{parse: 'get_error_count',parse_id: res['parse_id']},function(error_count) {
			             			document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+' и Закончился: '+time_stop.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>Было найдено: '+error_count+' ошибок.<br/>Обновите страницу!';
			             			clearInterval(cycle);
			             		},'json');
		             		}
			             	else document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>';
			             },'json')
			         },10000);
	        }
	        else document.getElementById('message').innerHTML=res['error'];
	    },'json');
    }
}
</script>
</head>
<body>
	<span style="display:block;margin-left:auto;margin-right:auto;font-size:22px;color:purple;" onclick="parse_stat();">СТАТИСТИКА</span><br/> 
	
	<span style="display: none;" id="shop_id">#SHOP_ID#</span><span style="display: none;" id="user_id">#USER_ID#</span>
	<div>
		<span style="color:#FF3300; font-size:large; font-weight:200;" id="message">#MESSAGE#</span>
	</div>
	<br/>
	<div style="margin-left:auto;margin-right:auto">
	Добро пожаловать в Статистику по магазинам<br/><br/>
	
	<form action="" method="post" enctype="multipart/form-data">
		<span>Тип прокси:</span><br/>
		<input type="radio" name="proxy" id="proxy_s" value="static"/><label for="proxy_s">Наши</label>
		<br/>
		<input type="radio" name="proxy" id="proxy_d" value="dynamic" checked="checked"/><label for="proxy_d">Покупные</label>
		<br/><br/>
		<!-- <input type="file" name="upload" /><br/> --></br/>
		<input type="submit" value="Запустить Парсер!" name="parse" />
	</form>
	</div><br/><br/>
	<span>Последний сбор Статистики был за: <strong>#LAST_STAT#</strong></span>
	<form action="" method="POST" name="download">
		<br/>
		<!-- <input type="radio" name="type" id="type_yandexData" value="report" /><label for="type_yandexData">Отчёт</label> -->
		<input type="submit" name="download" value="Сформировать и Скачать" #FILE_GEN_DIS# />
	</form>
	<!--<br />
	<br />
	<form action="" method="POST">
		<input type="submit" onClick="confirm(\'Уверены, что хотите очистить базу???\')" value="Очистить Таблицу" #FILE_GEN_DIS# name="TRUNCATE"/>
	</form>
	<br />
	<br />
	<form action="" method="POST">
		<input type="submit" onClick="confirm(\'Уверены, что хотите очистить кэш???\')" value="Очистить Кэш" #CASH_CLEAN# name="Cache"/>
	</form>
	<br/>
	<br/> --></br/>
	<a style="color:#CCC; font-size:12px;" href="?logout=now">Выход</a>
</body>
<html>