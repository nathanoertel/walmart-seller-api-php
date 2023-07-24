<?php
namespace WalmartSellerAPI;

abstract class AbstractJSONResponse extends AbstractResponse {
	protected function __loadData($response, $method) {
		$this->data = json_decode($response, true);
	}
}
?>