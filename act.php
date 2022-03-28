<?php
	if ($_SERVER["REQUEST_METHOD"]=="POST") {
		if( isset( $_POST['LedOn'] ) )
			$state = 1;
		if( isset( $_POST['LedOff'] ) )
			$state = 0;
		// читаем данные для соединения с БД
		$config = require 'db_conn.php';
		
		// Создаем соединение
		$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

		// Проверяем соединение
		if ($conn->connect_error)
		  die("Connection failed: " . $conn->connect_error);

		// берем данные датчика из базы
		$sql = "SELECT devices.address as addr, controls.name as name, controls.id_control as id_c
			FROM devices, controls
			WHERE controls.id_device = controls.id_device AND controls.name='D8'";
		$res_sens = $conn->query($sql);
		$sens = $res_sens->fetch_object();
		$sURL = 'http://'.$sens->addr.'/act?'.$sens->name.'='.$state; // URL-адрес GET 
		if( file_get_contents($sURL) == false) 
			echo "Ошибка отправки команды в устройство";
		else {
			try {
				// вносим запись о выполнении команды
				$sql = "INSERT INTO commands (id_control, command, run, date_run)
					VALUES (".$sens->id_c.", ".$state.", 1, NOW())";
				$conn->query($sql);
			}
			catch(Exception $e) {
				$conn->rollback();
				echo $e;
			}
			$conn->commit();
		}
	}
?>

<!DOCTYPE html>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">

<head>
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
