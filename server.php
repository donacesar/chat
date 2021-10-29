<?php
//определяем порт на каком будет работать сервер
define('PORT', "8090");

require_once ("classes/Chat.php");

$chat = new Chat();

/**
  * Создаём сокет
  * AF_INET - семейство адресов - IPv4
  * SOCK_STREAM - тип сокета - передача потока данных с предварительной установкой соединения.
  * SOL_TCP - протокол TCP
*/
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// Конфигурируем сокет: 
// SQL_SOCKET - устанавливаем уровень протокола на уровне сокета
// Опция SO_REUSEADDR - Сообщает, могут ли локальные адреса использоваться повторно.
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Привязываем используемый порт к сокету
socket_bind($socket, 0, PORT);

//включаем прослушивание сокета
socket_listen($socket);

echo "Чат-сервер запущен\n";


// Клиентов может подключиться много, по-этому создаем массив подключенных сокетов
$clientSocketArray = array($socket);

// Создаем бесконечный цикл работы сервера
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

        /*Чистим массив $newSocketArray от отработанных сокетов*/

        // Находим индекс отработанного сокета 
        $newSocketArrayIndex = array_search($socket, $newSocketArray);

        // Удаляем сокет из массива по найденному индексу
        unset($newSocketArray[$newSocketArrayIndex]);
    }

    foreach($newSocketArray as $newSocketArrayResource) {
        
        // 1
        // Проверяем есть ли данные. Если есть - (> 1), нет - 0
        while(@socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {

            // Сообщение от клиента переводим обратно в JSON(unserialize) и декодируем
            $socketMessage = $chat->unseal($socketData);
            $messageObj = json_decode($socketMessage);

            // Сообщение готовое к отправке пользователям
            $chatMessage = $chat->createChatMessage($messageObj->chat_user, $messageObj->chat_message);

            $chat->send($chatMessage, $clientSocketArray);

            break 2;
        }

        // 2 Обработка тех, кто покинул чат
        $socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
        if($socketData === false) {
            // получаем ip адрес пользователя, который вышел из сети 
            socket_getpeername($newSocketArrayResource, $client_ip_address);
            // создаем сообщение о выходе, чтобы потом разослать членам чата
            $connectionACK = $chat->newDisconnectedACK($client_ip_address);
            $chat->send($connectionACK, $clientSocketArray);

            // В массиве сокетов клиентов ищем оборванный сокет и удаляем его
            $newSocketArrayIndex = array_search($newSocketArrayResource, $clientSocketArray);
            unset($clientSocketArray[$newSocketArrayIndex]);
        }
    }

    
}

// Закрываем сокет для порядка 
socket_close($socket);