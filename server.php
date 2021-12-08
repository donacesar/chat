<?php
/* Файл, запускающий сокет-сервер чата. */

require_once __DIR__ . '/config.php';
require __DIR__ . '/autoload.php';

use classes\Chat;
use classes\Log;
use classes\Pid;

$chat = new Chat();
$log = new Log(LOG_HTML);
$pid = new Pid(PID);

$log->message('Пытаюсь запустить...');

if ($pid->isActive()) {
   $log->message('CANCEL - чат уже запущен');
   exit(0);
}

// Сохраняем PID в файл
file_put_contents(PID, getmypid());

$log->message('Пытаюсь запустить сокет...');

$socket = new Socket(IP, PORT);

$log->message('Чат-сервер запущен');

// Клиентов может подключиться много, по-этому создаем массив подключенных сокетов
$clientSocketArray = array();

// Создаем бесконечный цикл работы сервера
while(true) {

    // Каждую итерацию записываем мастер-сокет $socket в массив, т.к. После выполнения функции socket_select(), в этом массиве будут содержаться только те сокеты, на которых есть доступные для чтения данные, остальные будут удалены.
    $newSocketArray = $clientSocketArray;
    $newSocketArray[] = $socket->get_socket();

    $socket->new_connection($newSocketArray, $clientSocketArray);

    foreach($newSocketArray as $newSocketArrayResource) {
        
        // 1
        // Проверяем количество поступивших байт (есть ли данные. Если есть - (> 1), нет - 0)
        $dataSize = socket_recv($newSocketArrayResource, $socketData, 1024, 0);

        
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

            if ($messageObj->chat_message === 'OFF') {
                $log = new Log(LOG_HTML);
                $log->message('OFF - чат-сервер выключился');
                unset($socket);
                unlink(PID);
                exit(0);
            }

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
unset($socket);
unlink(PID);