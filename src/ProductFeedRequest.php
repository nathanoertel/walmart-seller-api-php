<?php
namespace WalmartSellerAPI;

class ProductFeedRequest extends FeedRequest {

	public function submit($type, $items) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$header->feedDate = $time->format(\DateTime::ATOM);

		$feed = new ItemFeed();

		$feed['MPItemFeedHeader'] = array(
			'version' => '3.2',
			'feedDate' => $time->format(\DateTime::ATOM)
		);

		$feed[$type] = $items;
		
		return $this->post('?feedType=item', $feed->asXML());
	}
	
	public function getFeed() {
		$document = Library::getDocument('MPItemFeed');
		return $document->getType();
	}
}