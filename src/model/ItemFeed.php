<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ItemFeed extends AbstractModel {
    public function __construct($data = null) {
        parent::__construct('product/MPItemFeed', $data);
    }
}
?>