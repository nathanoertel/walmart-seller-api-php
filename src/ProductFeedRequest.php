<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\ItemFeed;

class ProductFeedRequest extends AbstractJSONRequest {
	protected static $utcTimezone;
	protected static $timezone;

	public function submit($type, $items) {
		$feed = [
			'MPItemFeedHeader' => [
				'version' => '1.5',
				'processMode' => 'REPLACE',
				'subset' => 'EXTERNAL',
				'locale' => 'en',
				'sellingChannel' => 'marketplace',
				'feedDate' => self::getTimestamp(time())
			],
			'MPItem' => $items,
		];
		
		return $this->post('?feedType=MP_ITEM', $feed);
	}

	public function getEndpoint() {
		return '/v3/feeds';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\ProductFeedResponseJSON';
	}

	public static function getTimestamp($timestamp, $format = 'Y-m-d\TH:i:s.u\Z') {
		if(self::$utcTimezone === null) self::$utcTimezone = new \DateTimeZone("UTC");
		if(self::$timezone === null) self::$timezone = new \DateTimeZone(date_default_timezone_get());

		$time = new \DateTime();
		$time->setTimezone(self::$timezone);
		$time->setTimestamp($timestamp);
		$time->setTimezone(self::$utcTimezone);
		return $time->format($format);
	}
}