<?php
namespace WalmartSellerAPI;

abstract class AbstractResponse {

	protected $data = null;

	private $success = true;

	private $error = null;

	private $errorCode = null;

	private $errorMessage = null;
	
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
		if(isset($this->headers[$key])) return $this->headers[$key];
		else return null;
	}

	public function getResults() {
		return $this->data;
	}

	protected abstract function __loadData($response, $method);

	protected function getResponseOrGzippedResponse($response)
	{
			foreach($this->headers as $header => $value) {
					if(strtolower($header) === 'content-encoding') {
							if(strtolower($value) == 'gzip') {
								return gzdecode($response);
							}
							return $response;
					}
			}
			return $response;
	}

	public function __construct($headers, $response, $method) {
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
		
		$this->response = $this->getResponseOrGzippedResponse($response);

		if($httpCode >= 200 && $httpCode < 300) {
			if(($xml = $this->__loadData($this->response, $method)) === false) {
				$this->success = false;
				$this->errorCode = $httpCode;
				$this->error = $httpStatus;
				$this->errorMessage = $this->response;
			}
		} else {
			$this->success = false;
			$this->errorCode = $httpCode;
			$this->error = $httpStatus;
			$this->errorMessage = $this->response;
			if(!empty($this->response)) $this->__loadData($this->response, $method);
		}
	}
}
?>