<?php
namespace WalmartSellerAPI;

class OrderAcknowledgementRequest extends AbstractRequest {

	public function acknowledge($purchaseOrderId) {
		return $this->post('/'.$purchaseOrderId.'/acknowledge', null);
	}

	public function getEndpoint() {
		return '/v3/orders';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\OrderAcknowledgementResponse';
	}

	protected function init() {
		// check that the necessary keys are set
		if(!isset($this->config['channelTypeId'])) {
			throw new \Exception('Configuration missing channelTypeId');
		}

		Library::load('orders/PurchaseOrderV3');
	}

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}