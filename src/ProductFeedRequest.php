<?php
namespace WalmartSellerAPI;

class ProductFeedRequest extends FeedRequest {

	public function submit($feed) {
		$document = Library::getDocument('MPItemFeed');
		
		$header = Library::getType('mp/MPItemFeedHeader');

		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$header->feedDate = $time->format(\DateTime::ATOM);
		$header->version = '2.1';
		
		$feed->MPItemFeedHeader = $header;
		
		return $this->post('?feedType=item', $document->getXML($feed)->asXML());
	}
	
	public function getFeed() {
		$document = Library::getDocument('MPItemFeed');
		return $document->getType();
	}

	protected function init() {
		Library::load('mp/MPItemFeed');
		parent::init();
	}
}