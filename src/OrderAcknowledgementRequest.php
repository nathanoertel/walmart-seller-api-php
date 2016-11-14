<?php
namespace WalmartSellerAPI;

class OrderAcknowledgementRequest extends AbstractRequest {

	public function acknowledge($purchaseOrderId) {
		return $this->post('/'.$purchaseOrderId.'/acknowledge');
	}

	public function getEndpoint() {
		return '/v3/orders';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\OrderAcknowledgementResponse';
	}

	protected function init() {
		Library::load('orders/PurchaseOrderV3');
	}

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}

	public function __construct(array $config = [], $env = self::ENV_PROD) {

		// check that the necessary keys are set
		if(!isset($config['channelTypeId'])) {
			throw new \Exception('Configuration missing channelTypeId');
		}

		parent::__construct($config, $env);
	}
}