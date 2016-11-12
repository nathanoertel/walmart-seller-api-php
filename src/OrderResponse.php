<?php
namespace WalmartSellerAPI;

class OrderResponse extends AbstractResponse {

	protected function getLibrary() {
		return 'orders/PurchaseOrderV3';
	}
}