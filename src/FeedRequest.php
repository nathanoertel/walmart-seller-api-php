<?php
namespace WalmartSellerAPI;

class FeedRequest extends AbstractRequest {

	public function getEndpoint() {
		return '/v2/feeds';
	}

	protected function getResponse() {
		return 'FeedResponse';
	}
}