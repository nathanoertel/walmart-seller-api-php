<?php
namespace WalmartSellerAPI;

abstract class AbstractJSONResponse extends AbstractResponse {
	protected function __loadData($response, $method) {
		$this->data = json_decode($response, true);
	}

	public function __construct($response, $method) {
		$this->headers = $response->getHeaders();
		$httpCode = $response->getStatusCode();
		$httpStatus = $response->getReasonPhrase();
		
		$this->response = $this->getResponseOrGzippedResponse($response->getBody());

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