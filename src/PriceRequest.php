<?php
namespace WalmartSellerAPI;

class PriceRequest extends AbstractRequest {

	public function update($sku, $price, $salePrice = null) {
		$document = Library::getDocument('Price');

		$p = $document->getType();
		$itemIdentifier = Library::getType('prices/itemIdentifierType');
		$itemIdentifier->sku = $sku;
		$p->itemIdentifier = $itemIdentifier;
		$pricingList = Library::getType('prices/pricingListType');
		$pricingListPricing = Library::getType('prices/pricingType');
		$currentPrice = Library::getType('prices/price');
		if($salePrice) {
			$comparisonPrice = Library::getType('prices/price');
			$comparisonPriceValue = Library::getType('prices/moneyType');
			$comparisonPriceValue->amount = $price;
			$comparisonPrice->value = $comparisonPriceValue;
			$currentPriceValue = Library::getType('prices/moneyType');
			$currentPriceValue->amount = $salePrice;
			$currentPrice->value = $currentPriceValue;
			$pricingListPricing->comparisonPrice = $comparisonPrice;
			$pricingListPricing->currentPriceType = 'REDUCED';
		} else {
			$currentPriceValue = Library::getType('prices/moneyType');
			$currentPriceValue->amount = $price;
			$currentPrice->value = $currentPriceValue;
		}
		$pricingListPricing->currentPrice = $currentPrice;
		$pricingList->pricing = $pricingListPricing;
		$p->pricingList = $pricingList;
		echo $document->getXML($p)->asXML();
		exit();
		return $this->put('', $document->getXML($p)->asXML());
	}

	public function getEndpoint() {
		return '/v3/price';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\PriceResponse';
	}

	protected function init() {
		Library::load('prices/BulkPriceFeed');
	}

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}

	public function __construct(array $config = [], $env = self::ENV_PROD) {

		// check that the necessary keys are set
		if(!isset($config['channelTypeId'])) {
			throw new \Exception('Configuration missing channelTypeId');
		}

		parent::__construct($config, $env);
	}
}