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

// Создаем бесконечныц цикл
while(true) {
    // Принимаем соединение на сокете
    $newSocket = socket_accept($socket);
    // принимаем заголовки клиента
    $header = socket_read($newSocket, 1024);
    $chat->sendHeaders($header, $newSocket, 
        "localhost", PORT);
}

// Закрываем сокет для порядка 
socket_close($socket);