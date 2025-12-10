<?php
namespace WalmartSellerAPI;

class OrderRequest extends AbstractXMLRequest {
	public static function getDate($timestamp) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$date = new \DateTime();
		$date->setTimezone($timezone);
		$date->setTimestamp($timestamp);
		$date->setTimezone($utcTimezone);
		return $date->format('Y-m-d\TH:i:s.u\Z');
	}

	public function order($purchaseOrderId) {
		return $this->get('/'.$purchaseOrderId);
	}

	private function parseNextCursorOrParams($paramsOrNextCursor) {
		if (is_string($paramsOrNextCursor)) {
			parse_str($paramsOrNextCursor, $params);
			return $params;
		}
		
		return $paramsOrNextCursor;
	}

	public function getOrders($paramsOrNextCursor) {
		$params = $this->parseNextCursorOrParams($paramsOrNextCursor);
		return $this->get('', $params);
	}
	
	
	public function orders($startDate, $nextCursor = null) {
		$params = array();
		
		if($nextCursor == null) {
			$utcTimezone = new \DateTimeZone("UTC");
			$timezone = new \DateTimeZone(date_default_timezone_get());
			
			$startTime = new \DateTime();
			$startTime->setTimezone($timezone);
			$startTime->setTimestamp($startDate);
			$startTime->setTimezone($utcTimezone);
		
			$params['createdStartDate'] = $startTime->format('Y-m-d\TH:i:s.u\Z');
		} else {
			parse_str($nextCursor, $params);
		}

		return $this->get('', $params);
	}
	
	public function releasedList($startDate, $nextCursor = null) {
		$params = array();
		
		if($nextCursor == null) {
			$utcTimezone = new \DateTimeZone("UTC");
			$timezone = new \DateTimeZone(date_default_timezone_get());
			
			$startTime = new \DateTime();
			$startTime->setTimezone($timezone);
			$startTime->setTimestamp($startDate);
			$startTime->setTimezone($utcTimezone);
		
			$params['createdStartDate'] = $startTime->format('Y-m-d\TH:i:s.u\Z');
		} else {
			parse_str($nextCursor, $params);
		}

		return $this->get('/released', $params);
	}

	public function getEndpoint() {
		return '/v3/orders';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\OrderResponse';
	}

	public function getHeaders($url, $method, $headers = array()) {
		if (isset($this->config['channelTypeId'])) $headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}