<?php
namespace WalmartSellerAPI;

class InventoryRequest extends AbstractRequest {

	public function find($sku) {
		return $this->get('', array(
			'sku' => $sku
		));
	}

	public function update($sku, $inventory, $fulfillment) {
		$document = Library::getDocument('inventory');

		$inv = $document->getType();
		$inv->sku = $sku;
		$quantity = Library::getType('inventory/Quantity');
		$quantity->unit = 'Each';
		$quantity->amount = $inventory;
		$inv->quantity = $quantity;
		$inv->fulfillmentLagTime = $fulfillment;
echo $document->getXML($inv)->asXML();
		return $this->put('?sku='.$sku, $document->getXML($inv)->asXML());
	}

	public function getEndpoint() {
		return '/v2/inventory';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\InventoryResponse';
	}

	protected function init() {
		Library::load('inventory/Inventory');
	}
}