<?php
namespace WalmartSellerAPI;

abstract class AbstractXMLResponse extends AbstractResponse {
	private $count = 0;
	
	private $offset = 0;
	
	private $limit = 100;
	
	public function getPageCount() {
		return ceil($this->count/$this->limit);
	}

	protected function getModel($name) {
		return 'WalmartSellerAPI\model\\'.$name;
	}

	protected function __loadData($response, $method) {
		$xml = @simplexml_load_string($response);
			
		if($xml === false) {
			$this->success = false;

			$json = json_decode($response, true);

			if ($json && isset($json['error'])) {
        $this->errorCode = (string)$json['error']['code'];
        $this->error = (string)$json['error']['info'];
        $this->errorMessage = (string)$json['error']['description'];
			}
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