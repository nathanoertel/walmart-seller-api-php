<?php
namespace WalmartSellerAPI;

class ShipConfirmRequest extends AbstractRequest {

	public function confirm($purchaseOrderId, $shipments) {
		$document = Library::getDocument('orderShipment');

		$doc = $document->getType();

		$lines = Library::getType('orders/shippingLinesType');

		foreach($shipments as $shipment) {
			$line = Library::getType('orders/shippingLineType');
			$line->lineNumber = $shipment['id'];
			$statuses = Library::getType('orders/shipLineStatusesType');
			$status = Library::getType('orders/shipLineStatusType');
			$status->status = 'Shipped';
			$quantity = Library::getType('orders/quantityType');
			$quantity->unitOfMeasurement = 'Each';
			$quantity->amount = $shipment['quantity'];
			$status->statusQuantity = $quantity;
			$trackingInfo = Library::getType('orders/trackingInfoType');

			$utcTimezone = new \DateTimeZone("UTC");
			$timezone = new \DateTimeZone(date_default_timezone_get());
			
			$shipTime = new \DateTime();
			$shipTime->setTimezone($timezone);
			$shipTime->setTimestamp($shipment['shipTime']);
			$shipTime->setTimezone($utcTimezone);
		
			$trackingInfo->shipDateTime = $shipTime->format(\DateTime::ATOM);
			$carrierName = Library::getType('orders/carrierNameType');
			if(empty($shipment['shippingProvider'])) $carrierName->otherCarrier = $shipment['shippingProviderName'];
			else $carrierName->carrier = $shipment['shippingProvider'];
			$trackingInfo->carrierName = $carrierName;
			$trackingInfo->methodCode = $shipment['shippingMethod'];
			$trackingInfo->trackingNumber = $shipment['trackingNumber'];
			$status->trackingInfo = $trackingInfo;
			$statuses->orderLineStatus = $status;
			$line->orderLineStatuses = $statuses;
			$lines->orderLine = $line;
		}

		$doc->orderLines = $lines;

		return $this->post('/'.$purchaseOrderId.'/shipping', $document->getXML($doc)->asXML());
	}

	public function getEndpoint() {
		return '/v3/orders';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\ShipConfirmResponse';
	}

	protected function init() {
		// check that the necessary keys are set
		if(!isset($this->config['channelTypeId'])) {
			throw new \Exception('Configuration missing channelTypeId');
		}

		Library::load('orders/ShipConfirmRequestV3');
		Library::load('orders/PurchaseOrderV3');
	}

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		return parent::getHeaders($url, $method, $headers);
	}
}