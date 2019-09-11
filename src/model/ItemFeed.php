<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class PartnerFeedResponse extends AbstractModel {
    public function __construct($data = null) {
        parent::__construct('product/MPItemFeed', $data);
    }
}
?>