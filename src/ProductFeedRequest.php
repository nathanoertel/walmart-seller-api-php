<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\ItemFeed;

class ProductFeedRequest extends FeedRequest {

	public function submit($type, $items) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$feed = new ItemFeed();

		$feed['MPItemFeedHeader'] = array(
			'version' => '3.2',
			'feedDate' => $time->format('Y-m-d\TH:i:s.u\Z')
		);

		$feed[$type] = $items;
		
		return $this->post('?feedType=item', $feed->asXML());
	}
}