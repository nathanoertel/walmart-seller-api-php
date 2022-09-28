<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\BulkPriceFeed;
use WalmartSellerAPI\model\InventoryFeed;

class FeedRequest extends AbstractRequest {
	protected static $utcTimezone;
	protected static $timezone;

	public function find($feedId, $params = array()) {
		$params['includeDetails'] = 'true';

		return $this->get('/'.$feedId, $params);
	}
	
	public function bulkUpdateProducts($products) {

	}

	public function bulkUpdatePricing($skus) {
		$feed = new BulkPriceFeed();

		$feed['PriceHeader'] = array(
			'feedDate' => self::getTimestamp(time()),
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
		$feed = new InventoryFeed();

		$feed['InventoryHeader'] = array(
			'version' => '1.4',
			'feedDate' => self::getTimestamp(time(), \DateTime::ATOM),
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

	public static function getTimestamp($timestamp, $format = 'Y-m-d\TH:i:s.u\Z') {
		if(self::$utcTimezone === null) self::$utcTimezone = new \DateTimeZone("UTC");
		if(self::$timezone === null) self::$timezone = new \DateTimeZone(date_default_timezone_get());

		$time = new \DateTime();
		$time->setTimezone(self::$timezone);
		$time->setTimestamp($timestamp);
		$time->setTimezone(self::$utcTimezone);
		return $time->format($format);
	}

	public function __construct(array $config = array(), $logger = null, $env = self::ENV_PROD)
	{
		parent::__construct($config, $logger, $env);
	}
}