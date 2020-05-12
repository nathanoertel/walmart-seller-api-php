<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\Inventory;

class InventoryRequest extends AbstractRequest {

	public function find($sku) {
		return $this->get('', array(
			'sku' => $sku
		));
	}

	public function update($sku, $quantity) {
		$inventory = new Inventory();

		$inventory['sku'] = $sku;
		$inventory['quantity'] = array(
			'unit' => 'EACH',
			'amount' => $quantity
		);

		return $this->put('?sku='.$sku, $inventory->asXML());
	}

	public function getEndpoint() {
		return '/v3/inventory';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\InventoryResponse';
	}
}