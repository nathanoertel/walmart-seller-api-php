<?php
namespace WalmartSellerAPI;

abstract class AbstractXMLRequest extends AbstractRequest {
	protected function getAcceptType() {
		return 'Accept: application/xml';
	}

	protected function formatResponse($response) {
		$xmlDocument = new \DOMDocument('1.0');
		$xmlDocument->preserveWhiteSpace = false;
		$xmlDocument->formatOutput = true;
		$xmlDocument->loadXML($response);

		return $xmlDocument->saveXML();
	}
}
?>