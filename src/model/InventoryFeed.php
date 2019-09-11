<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class InventoryFeed extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('inventory/InventoryFeed', $data);
    }
}
?>