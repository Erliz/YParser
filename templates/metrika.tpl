<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript" src="js/highcharts/highcharts.js"></script>
		<script type="text/javascript" src="js/highcharts/themes/gray.js"></script>
		<title>Olap Info</title>
	</head>
	<body>
		<ul style="position: fixed;margin-left: -20px;">			
			<li><a href="metrika.php">Выбор даты</a></li>
			<li><a href="#summary">Общий</a></li>
			<li><a href="#metrika">Переходы</a></li>
			<li><a href="#orders">Заказы</a></li>
		</ul>
		
		#HCHARTS#
		<div id="summary" style="width: 1200px; height: 700px; margin: 0 auto"></div>
		
		#HCHARTS_METRIKA#
		<div id="metrika" style="width: 1200px; height: 700px; margin: 100 auto;position: relative;"></div>
		
		#HCHARTS_ORDERS#
		<div id="orders" style="width: 1200px; height: 700px; margin: 100 auto;position: relative;"></div>
	</body>
</html>

