<?php
	if( isset( $_POST['LedOn'] ) )
		$state = 1;
	if( isset( $_POST['LedOff'] ) )
		$state = 0;
	$sURL = "http://192.168.0.103/act"; // URL-адрес POST 
	$sPD = "D8=".$state; // Данные POST
	$aHTTP = array(
	  'http' => // Обертка, которая будет использоваться
		array(
		'method'  => 'POST', // Метод запроса
		// Ниже задаются заголовки запроса
		'header'  => 'Content-type: application/x-www-form-urlencoded',
		'content' => $sPD
		)
	  );
	$context = stream_context_create($aHTTP);
	$contents = file_get_contents($sURL, false, $context);
?>

<!DOCTYPE html>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">

<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<title>Управление устройствами</title>
</head>

<body>
<form method="post" action="">
  <input type="submit" name="LedOn" value="Вкл" />
</form>
<form method="post" action="">
  <input type="submit" name="LedOff" value="Выкл" />
</form>
</body>
</html>
