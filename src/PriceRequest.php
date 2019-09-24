<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\Price;

class PriceRequest extends AbstractRequest {

	public function update($sku, $price, $salePrice = null) {
		$p = new Price();

		$p['itemIdentifier'] = array(
			'sku' => $sku
		);
		$p['pricingList'] = array(
			'pricing' => array(
				'currentPrice' => array(
					'value' => array(
						'amount' => $price
					)
				)
			)
		);

		if($salePrice) {
			$p['pricingList']['pricing']['currentPrice']['value']['amount'] = $salePrice;
			$p['pricingList']['pricing']['currentPriceType'] = 'REDUCED';
			$p['pricingList']['pricing']['comparisonPrice'] = array(
				'value' => array(
					'amount' => $price
				)
			);
		}

		return $this->put('', $p->asXML());
	}

	public function getEndpoint() {
		return '/v3/price';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\PriceResponse';
	}

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}