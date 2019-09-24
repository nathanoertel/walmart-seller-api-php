<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ItemPriceResponse extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('responses/ItemPriceResponse', $data);
    }
}
?>