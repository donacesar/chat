<?php 

namespace classes;

class Pid
{

	private $pid_file;
	
	public function __construct ($pid_file) {

		$this->pid_file = $pid_file;

	}

	public function isActive() {

		$log = new Log(LOG_HTML);

		if(is_file($this->pid_file)) {
			$pid = file_get_contents($this->pid_file);

			if (posix_kill($pid, 0)) {
				// Демон запущен
				return true;
			} else {
				// Pid-файл есть, но процесса нет
				if (!unlink($pid_file)) {
					$log->message('Ошибка. Не могу уничтожить pid-файл');
					exit(1);
				}
				$log->message('ok');
			}
		}
	return false;
	}


}