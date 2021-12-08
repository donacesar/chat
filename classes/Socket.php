<?php

namespace classes;

require_once __DIR__ . '/../config.php';

class Socket 
{
	private $socket;
	private $ip;
	private $port;

	public function __construct($ip, $port) {
		$this->ip = $ip;
		$this->port = $port;

  		/**********    Создаём сокет    **********/
  		// AF_INET - семейство адресов - IPv4
  		// SOCK_STREAM - тип сокета - передача потока данных с предварительной установкой соединения.
  		// SOL_TCP - протокол TCP
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		$log = new Log(LOG_HTML);
		if (false === $this->socket) {
    		$log->message('Ошибка. Сокет не доступен, причина : ' . socket_strerror(socket_last_error()));
    		unlink(PID);
   			$log->message('Чат-сервер остановлен');
    		exit(1);
		}

		// Конфигурируем сокет: 
		// SOL_SOCKET - устанавливаем уровень протокола на уровне сокета
		// Опция SO_REUSEADDR - Сообщает, могут ли локальные адреса использоваться повторно. Разрешаем использовать один порт для нескольких соединений
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

		// Привязываем используемый адрес и порт к сокету
		socket_bind($this->socket, IP, PORT);

		//включаем прослушивание сокета
		socket_listen($this->socket);

		$log->message('OK');
	}

	public function get_socket() {
		return $this->socket;
	}

	public function new_connection(&$newSocketArray, &$clientSocketArray) {
		// Т.к. socket_select не принимает значения null, создаем пустой массив
    	$nullArr = [];
    	// Ожидаем сокеты доступные для чтения 
    	socket_select($newSocketArray, $nullArr, $nullArr, 0, 10);

    	// Если $socket не удалился из массива - есть новое соединение
    	if (in_array($this->socket, $newSocketArray)) {

        	// Принимаем соединение на сокете
        	$newSocket = socket_accept($this->socket);
        	$clientSocketArray[] = $newSocket;

        	// Чистим массив $newSocketArray от отработанных сокетов 
        	unset($newSocketArray[array_search($this->socket, $newSocketArray)]);

        	// принимаем заголовки клиента
        	$header = socket_read($newSocket, 1024);
        	$chat = new Chat();
        	$chat->sendHeaders($header, $newSocket, "localhost", PORT);

        	// Узнаем IP adress клиента
        	socket_getpeername($newSocket, $client_ip_address); 
        	$connectionACK = $chat->newConnectionACK($client_ip_address);
        	$chat->send($connectionACK, $clientSocketArray);

    		$log = new Log(LOG_HTML);
    		$log->message('Новое соединение : ' . $client_ip_address);
    	}
	}

	public function __destruct () {
		socket_close($this->socket);
	}
}