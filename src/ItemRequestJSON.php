<?php
namespace WalmartSellerAPI;

class ItemRequestJSON extends AbstractJSONRequest {
	public function retire($sku) {
		return $this->delete('/'.$sku);
	}
	
	public function items($params) {
		return $this->get('', $params);
	}
	
	public function item($sku) {
		return $this->get('/'.$sku);
	}

	public function getEndpoint() {
		return '/v3/items';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\ItemResponseJSON';
	}
}