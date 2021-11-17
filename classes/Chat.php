<?php 

class Chat
{
	public function sendHeaders($headersText, $newSocket, $host, $port ) {

		//делаем массив для заголовков формата "ключь: значение"
		$headers = array();

		// Пришедшую строку с заголовками разбиваем на строки по регулярке(перевод строки) и кладем в массив $tmpLine
		$tmpLine = preg_split("/\r\n/", $headersText);

		// Перебираем массив, регуляркой парсим на ключ-значение и кладем в массив $headers 
		foreach ($tmpLine as $line) {
			$line = rtrim($line);
			if (preg_match("/^(\S+): (\S+)/", $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}


		// В массиве $headers забираем значение ключа 
		$key = $headers['Sec-WebSocket-Key'];

		// Кодируем ключ, запакованый в бинарную строку в формате Hex-строки, с верхнего разряда, закодированную sha1 с солью в base64
		$sKey = base64_encode(pack('H*', sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

		// Создаем строку заголовков, которую отправим обратно клиенту
		$strHeader = "HTTP/1.1 101 Switching Protocols \r\n" . 
			"Upgrade: websocket\r\n" . 
			"Connection: Upgrade\r\n" .
			"Web-Socket-Origin: $host\r\n" .
			"WebSocket-Location: ws://$host:$port/chat/server.php\r\n" .
			"Sec-WebSocket-Accept:$sKey\r\n\r\n";

		// Записываем заголовки в сокет
		socket_write($newSocket, $strHeader, strlen($strHeader));

	}

	//Отрабатывает новое(первое) подключение
	public function newConnectionACK($client_ip_adress) {
	 	$message = "$client_ip_adress"." connected";
	 	$messageArray = [

	 		"message" => $message,
	 		"type" => "newConnectionACK"
	 	];
	 	$ask = $this->seal(json_encode($messageArray));
	 	return $ask;
	 }

	 /* УПАКОВКА. Преобразуем архив с сообщением в строку со специальной последовательностью байт
	 Таким образом формируем фрейм нужной длины*/
	 public function seal($socketData) {
	 	// нефрагментированные фреймы - первый байт 0x81
	 	$b1 = 0x81;
	 	$length = strlen($socketData);
	 	$header = "";

	 	if($length <= 125) {
	 		$header = pack('CC', $b1, $length);
	 	}
	 	else if($length > 125 && $length < 65536) {
	 		$header = pack('CCn', $b1, 126, $length);
	 	}
	 	else if($length >= 65536) {
	 		$header = pack('CCNN', $b1, 127, $length);
	 	}
	 	return $header.$socketData;

	 }


	 // Передаем информацию в клиетскую часть
	 public function send($message, $clientSocketArray) {

	 	$messageLength = strlen($message);

	 	foreach($clientSocketArray as $clientSocket) {
	 		// Пишем в сокет
	 		socket_write($clientSocket, $message, $messageLength);
	 	}

	 	return true;
	 }


	 /* РАСПАКОВКА.  */
	 public function unseal($socketData) {
	 	// Определяем длину фрейма (7 бит '7 + 16'бит, '7+ 64)
	 	$length = ord($socketData[1]) & 127;

	 	// Определяем маску и данные для 3-х типов фреймов
	 	if ($length == 126) {
	 		$mask = substr($socketData, 4, 4);
	 		$data = substr($socketData, 8);
	 	
	 	} else if ($length == 127) {
	 		$mask = substr($socketData, 10, 4);
	 		$data = substr($socketData, 14);
	 	
	 	} else {
	 		$mask = substr($socketData, 2, 4);
	 		$data = substr($socketData, 6);
	 	}

	 	$socketStr = "";

	 	// Побайтово прогоняем данные через маску ^ - логическим ИЛИ
	 	for ($i=0; $i < strlen($data); $i++) {
	 		
	 		// Т.к. маска - 4 байта, то в индексе $mask используем остаток от деления на 4 
	 		$socketStr .= $data[$i] ^ $mask[$i%4];
	 	}


	 	return $socketStr;
	 }

	 public function createChatMessage($username, $messageStr) {

	 	$message = "<div>" . $username . " : " . $messageStr . "</div>";
	 	$messageArray = [
	 		'type' => 'chat-box',
	 		'message' => $message
	 	];

	 	return $this->seal(json_encode($messageArray));
	 }

	 //Отрабатывает выход из чата
	public function newDisconnectedACK($client_ip_address) {
	 	$message = "Client ". $client_ip_address." disconnected";
	 	$messageArray = [

	 		"message" => $message,
	 		"type" => "newConnectionACK"
	 	];
	 	$ask = $this->seal(json_encode($messageArray));
	 	return $ask;
	 }
}