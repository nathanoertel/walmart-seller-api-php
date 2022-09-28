<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\ItemFeed;

class ProductFeedRequest extends FeedRequest {

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
		
		return $this->post('?feedType=MP_ITEM', json_encode($feed));
	}

	protected function getPostContentType() {
		return 'Content-Type: application/json';
	}
	
	protected function getPostFields($data) {
		return $data;
	}
	
	protected function formatXml($data) {
		return json_encode(
			json_decode($data, true),
			JSON_PRETTY_PRINT
		);
	}

	public static function getTimestamp($timestamp, $format = DATE_ATOM) {
		return parent::getTimestamp($timestamp, $format);
	}
}