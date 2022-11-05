<?php
namespace WalmartSellerAPI\log;

interface Logger {
	
	public function info($message, $error = false);
}
?>