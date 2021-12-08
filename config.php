<?php
/* Конфигурация приложения ЧАТ */

// Определяем порт на каком будет работать сервер
define('PORT', '8090');

// Выводим все ошибки и предупреждения
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Время выполнения скрипта безгранично
set_time_limit(0);

// Включаем вывод без буферизации 
ob_implicit_flush();

// Выключаем зависимость от пользователя
ignore_user_abort(true);

//------------------------------------------

// Файл куда записывается PID запущенного процесса
const PID = __DIR__ . '/pid_file.pid';

// Переопределяем лог ошибок PHP
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/logs/error_php.log');

// Переопределяем стандартные потоки ввода-вывода
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen(__DIR__ . '/logs/output.log', 'a');
$STDERR = fopen(__DIR__ . '/logs/error.log', 'a');

// Лог работы чат-сервера в html
const LOG_HTML = __DIR__ . '/logs/chatServerLog.html';

// Адрес и порт чат-сервера(сокета)
const IP = '0.0.0.0';
const PORT = 8090;

