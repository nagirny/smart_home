<!DOCTYPE html>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://code.highcharts.com/highcharts.js"></script>
<head>
  <title>Данные с датчиков</title>
</head>
<style>
	/* Remove outline on the forms and links */
	:active, :hover, :focus {
		outline: 0;
		outline-offset: 0;
	}
	body {
	  min-width: 310px;
	  max-width: 1280px;
	  height: 500px;
	  margin: 0 auto;
	}

	h2 {
	font-family: Arial;
	font-size: 200%;
	text-align: center;
	}
	h3 {
	font-family: Helvetica;
	font-size: 150%;
	text-align: center;
	color: #059e8a;
	}
	input {
	  outline: none;
	  border: 1px solid green;
	  border-radius: 5px;
	  padding: 4px;
	  text-align: center;
	  font-size: 80%;
	  color: #056e5a;
	  width: 5%;
	}
	input[type="number"]::-webkit-outer-spin-button,
	input[type="number"]::-webkit-inner-spin-button {
		-webkit-appearance: none; // Yeah, yeah everybody write about it
	}

	input[type='number'],
	input[type="number"]:hover,
	input[type="number"]:focus {
		appearance: none;
		-moz-appearance: textfield;
	}
</style>

<body>
<h2 style="color: #056e5a;">
  <FORM method= "GET" action="chart.php">Данные датчиков за
  <?php $per = $_GET['period'];
    if(!$per) $per=48;
    $s= '<INPUT name="period" value='.$per.' type="number">';
    echo $s;
    // часовой пояс Красноярска
    //$per = $per-4;
    ?> ч.
</h2>

<?php
date_default_timezone_set('Asia/Krasnoyarsk');

// читаем данные для соединения с БД
$config = require 'db_conn.php';
echo $config;
echo $config['host'];

// Создаем соединение
$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

// Проверяем соединение
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// цикл по перечню датчиков
$sql_sens = "SELECT devices.description as d_name, sensor.description as s_name, 
	sensor.id_sensor as id_s FROM devices, 
	sensor WHERE sensor.id_device = devices.id_device";
$res_sens = $conn->query($sql_sens);
while ($sens = $res_sens->fetch_object()) {
	$sql = "SELECT date as dd, data_float FROM sensor_data WHERE	id_sensor=" . 
		$sens->id_s . " AND date > DATE_SUB(now(), INTERVAL ".$per." HOUR) 
		order by date desc";
	$result = $conn->query($sql);
	$last=$result->fetch_assoc();
	// 50 точек на графике
	$interval = round($result->num_rows/100);
	$n=0;
	while ($data = $result->fetch_assoc()){
		$n++;
		if($n>$interval) {
			$sensor_data[] = array(strtotime($data['dd'])*1000, $data['data_float']);
			$n=0;
			}
		}
	$value1 = json_encode(array_reverse($sensor_data), JSON_NUMERIC_CHECK);
	echo $last['dd'];
	
	// создаем блок для вывода данных датчика
	echo '<div id="current-data" class="container">';
	echo '<h3>' . $sens->s_name . '  ' . $last['data_float'] . '</h3>';
	echo '</div>';
	echo '<div style="height:60%;" id="chart-' . $sens->id_s .'" class="container"></div>';
	?>
	<script>
	var value1 = <?php echo $value1; ?>;
	
	// рисуем график
	Highcharts.setOptions({
		time: {
			timezoneOffset: -7 * 60		// UTC +7
			}
		});
	var chartT = new Highcharts.Chart({
		chart:{ renderTo : <?php echo '"chart-'.$sens->id_s . '"'; ?> },
		title: { text: "" },
		series: [{
			showInLegend: false,
			data: value1,
			color: '#059e8a',
			name: <?php echo "'".$sens->s_name."'"; ?>,
			type: 'spline'
			}],
		plotOptions: {
			spline: { animation: false, dataLabels: { enabled: false }},
			},
		xAxis: {
			type: 'datetime',
			dateTimeLabelFormats: {
				hour: '%H:%M',
				day: '%e.%m',
				week: '%e.%m',
				month: '%e.%m',
				year: '%e.%m'
				}
			},
		yAxis: {
			title: { text: <?php echo "'".$sens->s_name."'"; ?> }
			},
		credits: { enabled: false }
		});

	</script>
<?php
	$sensor_data = array();
	}
$res_sens->free();
$result->free();
$conn->close();
?>

</body>
</html>
