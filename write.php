<?php
// TODO: считывать настройки из файла
$servername = "localhost";

// Здесь указываем название БД
$dbname = "u5437sang_home";
// Указываем имя пользователя
$username = "u5437sang_smart";
// Указываем пароль
$password = "3amberlan";

// Рекомендуем не изменять данный API-ключ, он должен совпадать с ключом в скетче для платы
$api_key_value = "rb9347ncNy87nO7";

$api_key = $value1 = $value2 = $value3 = "";

echo $_SERVER["REQUEST_METHOD"];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $api_key = test_input($_POST["api_key"]);
    if(1) {
        $value1 = test_input($_POST["date"]);
        $value2 = test_input($_POST["data_float"]);
        $value3 = test_input($_POST["id_sensor"]);
        
        // Создаем соединение
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Проверяем соединение
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        
        $sql = "INSERT INTO sensor_data (date, data_float, id_sensor) 
		VALUES ('" . $value1 . "', " . $value2 . ", " . $value3 . ")";

	echo $sql;
        
        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } 
        else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    
        $conn->close();
    }
    else {
        echo "Wrong API Key provided.";
    }

  }
else {
    echo "Not a HTTP POST method.";
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
