<?php
namespace WalmartSellerAPI\log;

class FileLogger implements Logger {
	
	private $info;
	
	private $error;
	
	public function info($message, $error = false) {
		error_log($message."\n", 3, ($error ? $this->error : $this->info));
	}
	
	public function __construct($infoLogFile, $errorLogFile) {
		$this->info = $infoLogFile;
		$this->error = $errorLogFile;
	}
}