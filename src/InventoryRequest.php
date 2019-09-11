<?php
namespace WalmartSellerAPI;

class InventoryRequest extends AbstractRequest {

	public function find($sku) {
		return $this->get('', array(
			'sku' => $sku
		));
	}

	public function update($sku, $inventory, $fulfillment) {
		$inventory = new WalmartSellerAPI\model\Inventory();

		$inventory['sku'] = $sku;
		$inventory['quantity'] = array(
			'unit' => 'EACH',
			'amount' => $inventory
		);
		$inventory['fulfillmentLagTime'] = $fulfillment;

		return $this->put('?sku='.$sku, $inventory->asXML());
	}

	public function getEndpoint() {
		return '/v3/inventory';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\InventoryResponse';
	}
}