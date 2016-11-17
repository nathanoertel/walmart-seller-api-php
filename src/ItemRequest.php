<?php
namespace WalmartSellerAPI;

class ItemRequest extends AbstractRequest {

	public function retire($sku) {
		return $this->delete('/retire/'.$sku);
	}

	public function getEndpoint() {
		return '/v2/items';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\ItemResponse';
	}

	protected function init() {
		Library::load('mp/MPItemRetire');
	}
}