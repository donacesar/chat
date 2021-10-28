<?php 

class Chat
{
	public function sendHeaders($headersText, $newSocket, $host, $port ) {

		//делаем массив для заголовков формата ключь: значение
		$headers = array();

		// Пришедшую строку с заголовками разбиваем на строки по регулярке и кладем в массив $tmpLine
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

	 public function newConnectionACK($client_ip_adress) {
	 	$message = "New client ". $client_ip_adress." connected";
	 	$messageArray = [

	 		"message" => $message,
	 		"type" => "newConnectionACK"
	 	];
	 	$ask = $this->seal(json_encode($messageArray));
	 	return $ask;
	 }

	 // Преобразуем архив с сообщением в строку со специальной последовательностью байт
	 // Таким образом формируем фрейм нужной длины
	 public function seal($socketData) {
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
	 		@socket_write($clientSocket, $message, $messageLength);
	 	}

	 	return true;
	 }
}