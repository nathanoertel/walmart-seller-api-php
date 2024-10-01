<?php
namespace WalmartSellerAPI;

class InventoryRequestJSON extends AbstractJSONRequest {
	public function find($sku) {
		return $this->get('', array(
			'sku' => $sku
		));
	}

	public function update($sku, $quantity) {
		return $this->put('?sku='.$sku, [
			'sku' => $sku,
			'quantity' => [
				'unit' => 'EACH',
				'amount' => $quantity
			],
		]);
	}

	public function getEndpoint() {
		return '/v3/inventory';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\InventoryResponseJSON';
	}
}