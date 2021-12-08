<?php

/* Класс для вывода логов чат-сервера в HTML файл*/

namespace classes;

class Log 
{

	private $log;

	public function __construct($log) {

		$this->log = $log;
	}

	public function message($message) {

		$file = null;

		if (!file_exists($this->log)) {

			$header = "<!DOCTYPE html>\r\n<html>\r\n<head>\r\n<title>GC - console log</title>\r\n\r\n<meta charset=\"UTF-8\" />\r\n</head>\r\n<body>\r\n";

			$file = fopen($this->log, 'w');

			fputs($file, $header);

		} else {

			$file = fopen($this->log, 'a');

		}

		$message_html = "[<b>" . date("Y.m.d-H:i:s") . "</b>]" . $message . "<br>\r\n";

		fputs($file, $message_html);

		fclose($file);

		// Дублируем в поток вывода 
		echo date("Y.m.d - H:i:s") . $message . "\r\n";

	}

}

