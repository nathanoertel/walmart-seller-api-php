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
		
		$httpType = 'HTTP/1.1';
		$httpCode = 500;
		$httpStatus = '';
		
		foreach($headerArray as $index => $header) {
			if(strpos($header, 'HTTP/1.1') === 0) {
				$this->headers['http_code'] = $header;
				$httpCodeList = explode(' ', $header);
				$httpType = array_shift($httpCodeList);
				$httpCode = array_shift($httpCodeList);
				$httpCode = intval($httpCode);
				$httpStatus = implode(' ', $httpCodeList);
			} else if(!empty($header)) {
				list($key, $value) = explode(': ', $header);
				$this->headers[$key] = $value;
			}
		}

		if($httpCode >= 200 || $httpCode < 300) {
			if(($xml = $this->__loadXML($response, $method)) === false) {
				$this->success = false;
				$this->errorCode = $httpCode;
				$this->error = $httpStatus;
				$this->errorMessage = $response;
			}
		} else {
			$this->success = false;
			$this->errorCode = $httpCode;
			$this->error = $httpStatus;
			$this->errorMessage = $response;
			if(!empty($response)) $this->__loadXML($response, $method);
		}
	}

	private function __loadXML($response, $method) {
		$xml = simplexml_load_string($response);
			
		if($xml === false) {
			return $xml;
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
			if($xml->getName() == 'html') {
				$this->success = false;
				$dom = new \DOMDocument();
				@$dom->loadHTML($response);

				foreach($dom->getElementsByTagName('h1') as $code) {
					$this->errorCode = $code->nodeValue;
					break;
				}

				foreach($dom->getElementsByTagName('h2') as $code) {
					$this->error = $code->nodeValue;
					$this->errorMessage = $code->nodeValue;
					break;
				}
			} else {
				$name = $this->getModel($xml->getName());
				if(class_exists($name)) {
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
}
?>