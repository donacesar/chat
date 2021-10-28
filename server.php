<?php
//определяем порт на каком будет работать сервер
define('PORT', "8090");

require_once ("classes/Chat.php");

$chat = new Chat();

//создаем сокет на TCP соединении
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// Конфигурируем сокет: 
// SQL_SOCKET - устанавливаем уровень протокола на уровне сокета
// Опция SO_REUSEADDR - Сообщает, могут ли локальные адреса использоваться повторно.
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Привязываем используемый порт к сокету
socket_bind($socket, 0, PORT);

//включаем прослушивание сокета
socket_listen($socket);


// Клиентов может подключиться много, по-этому создаем массив подключенных сокетов
$clientSocketArray = array($socket);

// Создаем бесконечныц цикл
while(true) {

    $newSocketArray = $clientSocketArray;
    $nullA = [];
    socket_select($newSocketArray, $nullA, $nullA, 0, 10);

    if (in_array($socket, $newSocketArray)) {
        // Принимаем соединение на сокете
        $newSocket = socket_accept($socket);
        $clientSocketArray[] = $newSocket;

        // принимаем заголовки клиента
        $header = socket_read($newSocket, 1024);
        $chat->sendHeaders($header, $newSocket, "localhost", PORT);

        // Узнаем IP adress клиента
        socket_getpeername($newSocket, $client_ip_adress); 
        $connectionACK = $chat->newConnectionACK($client_ip_adress);
        $chat->send($connectionACK, $clientSocketArray);
    }

    
}

// Закрываем сокет для порядка 
socket_close($socket);