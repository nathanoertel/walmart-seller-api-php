<?php
namespace WalmartSellerAPI;

abstract class AbstractJSONRequest extends AbstractRequest {
	protected function getPostContentType() {
		return 'Content-Type: application/json';
	}

	protected function getAcceptType() {
		return 'Accept: application/json';
	}

	protected function formatResponse($response) {
		return json_encode(
			json_decode($response, true),
			JSON_PRETTY_PRINT
		);
	}
}
?>