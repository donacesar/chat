<?php
//определяем порт на каком будет работать сервер
define('PORT', '8090');

require_once ('classes/Chat.php');

// Переопределяем вывод 
$logDir = __DIR__ . '/logs';
ini_set('error_log', '/error_log');
/*fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen($logDir . '/output.log', 'a');
$STDOUT = fopen($logDir . '/error.log', 'a');*/

$chat = new Chat();

/**
  * Создаём сокет
  * AF_INET - семейство адресов - IPv4
  * SOCK_STREAM - тип сокета - передача потока данных с предварительной установкой соединения.
  * SOL_TCP - протокол TCP
*/
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// Конфигурируем сокет: 
// SOL_SOCKET - устанавливаем уровень протокола на уровне сокета
// Опция SO_REUSEADDR - Сообщает, могут ли локальные адреса использоваться повторно. Разрешаем использовать один порт для нескольких соединений
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Привязываем используемый адрес и порт к сокету
socket_bind($socket, '0.0.0.0', PORT);

//включаем прослушивание сокета
socket_listen($socket);

echo "Чат-сервер запущен\n";

// Клиентов может подключиться много, по-этому создаем массив подключенных сокетов
$clientSocketArray = array();

// Создаем бесконечный цикл работы сервера
while(true) {

    // Каждую итерацию записываем мастер-сокет $socket в массив, т.к. После выполнения функции socket_select(), в этом массиве будут содержаться только те сокеты, на которых есть доступные для чтения данные, остальные будут удалены.
    $newSocketArray = $clientSocketArray;
    $newSocketArray[] = $socket;

    // Т.к. socket_select не принимает значения null, создаем пустой массив
    // $write = $except = null;
    $nullA = [];
    // Ожидаем сокеты доступные для чтения 
    socket_select($newSocketArray, $nullA, $nullA, 0, 10);

    // Если $socket не удалился из массива - есть новое соединение
    if (in_array($socket, $newSocketArray)) {

        // Принимаем соединение на сокете
        $newSocket = socket_accept($socket);
        $clientSocketArray[] = $newSocket;

        // Чистим массив $newSocketArray от отработанных сокетов 
        unset($newSocketArray[array_search($socket, $newSocketArray)]);

        // принимаем заголовки клиента
        $header = socket_read($newSocket, 1024);
        $chat->sendHeaders($header, $newSocket, "localhost", PORT);

        // Узнаем IP adress клиента
        socket_getpeername($newSocket, $client_ip_adress); 
        $connectionACK = $chat->newConnectionACK($client_ip_adress);
        $chat->send($connectionACK, $clientSocketArray);
    }



    foreach($newSocketArray as $newSocketArrayResource) {
        
        // 1
        // Проверяем количество поступивших байт (есть ли данные. Если есть - (> 1), нет - 0)
        // $dataSize = socket_recv($newSocketArrayResource, $socketData, 1024, 0);


        // Собираем все куски в массив фреймов, клеим в строку
        $framesArr = [];
        $dataSize = 0;
        while (socket_recv(true) {
            $recvBytes = 0;
            $recvBytes = socket_recv($newSocketArrayResource, $socketData, 1024, 0);
            $dataSize += $recvBytes;
            $framesArr[] = $socketData;
            if ($recvBytes == 0) {
                break;
            }

        }
        $socketData = implode('', $framesArr);





        echo "\nПришло $dataSize байт\nПкрвые 8 байт";
        $unpackArr = unpack('h8', $socketData);
        print_r($unpackArr);

        // Пока здесь while (планировался цикл для чтения нескольких фреймов)
        while($dataSize) {

            // костыль: при закрытии окна браузера клиента передается $dataSize = 8 байт
            if ($dataSize == 8) {
                break;
            }
            // Сообщение от клиента декодируем и переводим обратно в JSON(unserialize)
            $socketMessage = $chat->unseal($socketData);
            $messageObj = json_decode($socketMessage);

            // Сообщение готовое к отправке пользователям
            $chatMessage = $chat->createChatMessage($messageObj->chat_user, $messageObj->chat_message);

            $chat->send($chatMessage, $clientSocketArray);

            break 2;
        }

        // 2 Обработка тех, кто покинул чат

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

// Закрываем сокет для порядка
// Не работает пока нет команды об остановке чат-сервера
socket_close($socket);