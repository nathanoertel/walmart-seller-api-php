<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\BulkPriceFeed;
use WalmartSellerAPI\model\InventoryFeed;

class FeedRequest extends AbstractRequest {

	public function find($feedId, $params = array()) {
		$params['includeDetails'] = 'true';

		return $this->get('/'.$feedId, $params);
	}
	
	public function bulkUpdateProducts($products) {

	}

	public function bulkUpdatePricing($skus) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$feed = new BulkPriceFeed();

		$feed['PriceHeader'] = array(
			'feedDate' => $time->format('Y-m-d\TH:i:s.u\Z'),
			'version' => '1.5.1'
		);

		$feed['Price'] = array();
	
		foreach($skus as $sku => $prices) {
			$price = array(
				'itemIdentifier' => array(
					'sku' => $sku
				),
				'pricingList' => array(
					'pricing' => array(
						'currentPrice' => array(
							'value' => array(
								'amount' => $prices['price']
							)
						)
					)
				)
			);

			if($prices['salePrice']) {
				$price['pricingList']['pricing']['currentPrice']['value']['amount'] = $prices['salePrice'];
				$price['pricingList']['pricing']['currentPriceType'] = 'REDUCED';
				$price['pricingList']['pricing']['comparisonPrice'] = array(
					'value' => array(
						'amount' => $prices['price']
					)
				);
			}

			$feed['Price'][] = $price;
		}

		return $this->post('?feedType=price', $feed->asXML());
	}
	
	public function bulkUpdateInventory($skus) {
		$utcTimezone = new \DateTimeZone("UTC");
		$timezone = new \DateTimeZone(date_default_timezone_get());
		
		$time = new \DateTime();
		$time->setTimezone($timezone);
		$time->setTimestamp(time());
		$time->setTimezone($utcTimezone);
	
		$feed = new InventoryFeed();

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
				)
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