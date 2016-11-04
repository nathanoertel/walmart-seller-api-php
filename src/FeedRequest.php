<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI/AbstractRequest;

class WalmartSellerAPI_FeedRequest extends WalmartSellerAPI_AbstractReqeust {

	public function getEndpoint() {
		return '/v2/feeds';
	}
}