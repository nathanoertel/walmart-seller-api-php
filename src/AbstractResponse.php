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

	abstract protected function getLibrary();

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

		$xml = simplexml_load_string($response);

		echo $xml->getName()."\n";

		if($xml->getName() == 'errors') {
			$error = $xml->children('http://walmart.com/');
			$this->success = false;
			$this->errorCode = (string)$error->code;
			$this->error = (string)$error->field;
			$this->errorMessage = (string)$error->description;
		} else {
			Library::load($this->getLibrary());
			$document = Library::getDocument($xml->getName());
			$this->data = Library::getType($document['type']);
			switch($method) {
				case AbstractRequest::GET:
					$this->data->parse($xml);
					break;
				case AbstractRequest::ADD:
					break;
				case AbstractRequest::FIND:
					break;
				case AbstractRequest::UPDATE:
					break;
				case AbstractRequest::DELETE:
					break;
			}
		}
	}
}
?>