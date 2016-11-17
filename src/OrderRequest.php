<?php
namespace WalmartSellerAPI;

class OrderRequest extends AbstractRequest {
	
	public function releasedList($startDate, $nextCursor = null) {
		$params = array();
		
		if($nextCursor == null) {
			$utcTimezone = new \DateTimeZone("UTC");
			$timezone = new \DateTimeZone(date_default_timezone_get());
			
			$startTime = new \DateTime();
			$startTime->setTimezone($timezone);
			$startTime->setTimestamp($startDate);
			$startTime->setTimezone($utcTimezone);
		
			$params['createdStartDate'] = $startTime->format(\DateTime::ATOM);
		} else {
			parse_str($nextCursor, $params);
		}

		return $this->get('/released', $params);
	}

	public function getEndpoint() {
		return '/v2/orders';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\OrderResponse';
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