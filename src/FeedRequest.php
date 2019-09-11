<?php
namespace WalmartSellerAPI;

class FeedRequest extends AbstractRequest {

	public function find($feedId, $params = array()) {
		Library::load('responses/ItemStatusDetail');
		
		if(empty($params)) $params['includeDetails'] = 'true';

		return $this->get('/feeds/'.$feedId, $params);
	}
	
	public function bulkUpdateProducts($products) {

	}

	public function bulkUpdatePricing($skus) {
		Library::load('prices/BulkPriceFeed');
		
		$document = Library::getDocument('PriceFeed');
		$feed = $document->getType();
		$header = Library::getType('prices/feedHeaderType');

		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$header->feedDate = $time->format(\DateTime::ATOM);
		$header->version = '1.5.1';
		
		$feed->PriceHeader = $header;
	
		foreach($skus as $sku => $prices) {
			$p = Library::getType('prices/itemPriceType');
			$itemIdentifier = Library::getType('prices/itemIdentifierType');
			$itemIdentifier->sku = $sku;
			$p->itemIdentifier = $itemIdentifier;
			$pricingList = Library::getType('prices/pricingListType');
			$pricingListPricing = Library::getType('prices/pricingType');
			$currentPrice = Library::getType('prices/price');
			if($prices['salePrice']) {
				$comparisonPrice = Library::getType('prices/price');
				$comparisonPriceValue = Library::getType('prices/moneyType');
				$comparisonPriceValue->amount = $prices['price'];
				$comparisonPrice->value = $comparisonPriceValue;
				$currentPriceValue = Library::getType('prices/moneyType');
				$currentPriceValue->amount = $prices['salePrice'];
				$currentPrice->value = $currentPriceValue;
				$pricingListPricing->comparisonPrice = $comparisonPrice;
				$pricingListPricing->currentPriceType = 'REDUCED';
			} else {
				$currentPriceValue = Library::getType('prices/moneyType');
				$currentPriceValue->amount = $prices['price'];
				$currentPrice->value = $currentPriceValue;
			}
			$pricingListPricing->currentPrice = $currentPrice;
			$pricingList->pricing = $pricingListPricing;
			$p->pricingList = $pricingList;
			$feed->Price = $p;
		}

		return $this->post('?feedType=price', $document->getXML($feed)->asXML());
	}
	
	public function bulkUpdateInventory($skus) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$feed = new WalmartSellerAPI\model\InventoryFeed();

		$feed['InventoryHeader'] = array(
			'version' => '1.4',
			'feedDate' => $time->format(\DateTime::ATOM),
		);

		$feed['inventory'] = array();

		foreach($skus as $sku => $inventory) {
			$feed['inventory'][] = array(
				'sku' => $sku,
				'quantity' => array(
					'unit' => 'EACH',
					'amount' => $inventory
				),
				'fulfillmentLagTime' => 1
			);
		}

		return $this->post('?feedType=inventory', $feed->asXML());
	}
	
	protected function getPostFields($data) {
		return array(
			'file' => $data
		);
	}
	
	protected function getPostContentType() {
		return 'Content-Type: multipart/form-data';
	}

	public function getEndpoint() {
		return '/v3/feeds';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\FeedResponse';
	}
}