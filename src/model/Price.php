<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class Price extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct(array(
            'prices/BulkPriceFeed',
            'prices/Price'
        ), $data);
    }
}
?>