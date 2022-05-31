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

	public function getHeaders($url, $method, $headers = array()) {
		if (isset($this->config['channelTypeId'])) $headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}