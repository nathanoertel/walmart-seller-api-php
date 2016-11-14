<?php
namespace WalmartSellerAPI\log;

interface Logger {
	
	public function log($message, $error = false);
}
?>