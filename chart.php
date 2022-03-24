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
  <FORM method= "GET" action="esp-chart.php">Данные датчиков за
  <?php $per = $_GET['period'];
    if(!$per) $per=48;
    $s= '<INPUT name="period" value='.$per.' type="number">';
    echo $s;
    ?> ч.
</h2>

<?php
// TODO: считывать настройки из файла
$servername = "localhost";

// Здесь указываем название БД
$dbname = "u5437sang_home";

// Указываем имя пользователя
$username = "u5437sang_smart";

// Указываем пароль
$password = "3amberlan";

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql_sens = "SELECT devices.description, sensor.description, sensor.id_sensor FROM devices, 
	sensor WHERE sensor.id_device = devices.id_device";
$res_sens = $conn->query($sql_sens);
echo "res_sens: ".$res_sens.;
while ($sens = $res_sens->fetch_assoc()) {
 echo ", 0: ".$sens[0].", 1: ".$sens[1]."\n";
 $sql = "SELECT data_float, date FROM sensor_data WHERE	id_sensor=" . 
	$sens['sensor.id_sensor'] . " AND date > DATE_SUB(now(), INTERVAL ".$per." HOUR) 
	order by date desc";
 echo $sql;
 $result = $conn->query($sql);
 $last=$result->fetch_assoc();
 $interval = round($result->num_rows/30);
 $n=0;
 while ($data = $result->fetch_assoc()){
	$n++;
	if($n>$interval) {
		$sensor_data[] = $data;
		$n=0;
	}
 }
 $sensor_data[] = $data;

 $readings_time = array_column($sensor_data, 'date');

 $value1 = json_encode(array_reverse(array_column($sensor_data, 'data_float')), JSON_NUMERIC_CHECK);
 $reading_time = json_encode(array_reverse($readings_time), JSON_NUMERIC_CHECK);
}
$res_sens->free();
$result->free();
$conn->close();
?>

<div id="current-temp" class="container">
	<h3>Температура<?php echo ": ".$last['value1']." C"?></h3>
</div>
<div style="height:60%;" id="chart-temperature" class="container"></div>
<div id="current-hum" class="container">
	<h3 style="color:#18009c">Влажность<?php echo ": ".$last['value2']." %"?></h3>
</div>
<div style="height:60%;" id="chart-humidity" class="container"></div>

<script>
var value1 = <?php echo $value1; ?>;
var reading_time = <?php echo $reading_time; ?>;

var chartT = new Highcharts.Chart({
	chart:{ renderTo : 'chart-temperature' },
	title: { text: "" },
	series: [{ showInLegend: false,	data: value1, color: '#059e8a', name: 'Температура', type: 'spline' }],
	plotOptions: {
		spline: { animation: false, dataLabels: { enabled: false }},
		},
	xAxis: {
		type: 'datetime',
		categories: reading_time
		},
	yAxis: {
		title: { text: 'Температура (С)' }
		},
	credits: { enabled: false }
	});

</script>
</body>
</html>
