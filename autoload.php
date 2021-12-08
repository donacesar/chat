<?php

echo "autoload зашёл\r\n";

function donacesar_autoload(string $class) {

	$filename = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
	
	if (file_exists($filename)) {
		include $filename;
	}

}

spl_autoload_register('donacesar_autoload');