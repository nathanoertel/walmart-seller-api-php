<?php
namespace WalmartSellerAPI;

class OrderRequest extends AbstractRequest {
	
	public function orders($startDate, $nextCursor = null) {
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
		
			$params['createdStartDate'] = $startTime->format(\DateTime::ATOM);
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
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}