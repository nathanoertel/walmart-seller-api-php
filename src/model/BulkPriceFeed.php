<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class BulkPriceFeed extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct(array(
            'prices/BulkPriceFeed',
            'prices/PriceFeed'
        ), $data);
    }
}
?>