<?php
namespace WalmartSellerAPI;

abstract class AbstractResponse {

	protected $data = null;

	private $success = true;

	private $error = null;

	private $errorCode = null;

	private $errorMessage = null;
	
	private $count = 0;
	
	private $offset = 0;
	
	private $limit = 100;
	
	private $response = null;
	
	private $headers = array();

	public function isSuccess() {
		return $this->success;
	}

	public function getError() {
		return $this->error;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}
	
	public function getPageCount() {
		return ceil($this->count/$this->limit);
	}
	
	public function getRawResponse() {
		return $this->response;
	}
	
	public function getHeader($key) {
		if(isset($this->headers[$key])) return $this->headers($key);
		else return null;
	}

	public function getResults() {
		return $this->data;
	}

	protected function getModel($name) {
		return 'WalmartSellerAPI\model\\'.$name;
	}

	public function __construct($headers, $response, $method) {
		$this->response = $response;
		
		$headerArray = explode("\r\n", $headers);
		
		foreach($headerArray as $index => $header) {
			if(strpos($header, 'HTTP/1.1') === 0) {
				$this->headers['http_code'] = $header;
			} else if(!empty($header)) {
				list($key, $value) = explode(': ', $header);
				$this->headers[$key] = $value;
			}
		}
		
		if($this->headers['http_code'] == 'HTTP/1.1 503 Service Unavailable') {
			$this->success = false;
			$this->errorCode = 503;
			$this->error = 'Service Unavailable';
			$this->errorMessage = 'Service Unavailable';
		} else if($this->headers['http_code'] == 'HTTP/1.1 400 Bad Request') {
			$this->success = false;
			$this->errorCode = 400;
			$this->error = 'Bad Request';
			$this->errorMessage = 'Bad Reqeust';
		} else {
			$xml = simplexml_load_string($response);
			
			if($xml === false) {
				$this->success = false;
				$this->errorCode = 500;
				$this->error = $response;
				$this->errorMessage = $response;
			} else if($xml->getName() == 'errors') {
				$this->success = false;
				$error = $xml->children('http://walmart.com/');
				if(empty($error)) {
					$this->errorCode = (string)$xml->error->code;
					$this->error = (string)$xml->error->info;
					$this->errorMessage = (string)$xml->error->description;
				} else {
					$this->errorCode = (string)$error->children('http://walmart.com/')->code;
					$this->error = (string)$error->children('http://walmart.com/')->field;
					$this->errorMessage = (string)$error->children('http://walmart.com/')->description;
				}
			} else {
				$name = $this->getModel($xml->getName());
				$this->data = new $name($xml);
				switch($method) {
					case AbstractRequest::GET:
						break;
					case AbstractRequest::ADD:
						break;
					case AbstractRequest::PUT:
						break;
					case AbstractRequest::UPDATE:
						break;
					case AbstractRequest::DELETE:
						break;
				}
			}
		}
	}
}
?>