<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\Inventory;

class InventoriesRequest extends AbstractJSONRequest {
	protected function getAcceptType() {
		return 'Accept: application/json';
	}

	public function getEndpoint() {
		return '/v3/inventories';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\InventoriesResponse';
	}
}