<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class Inventory extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('inventory/Inventory', $data);
    }
}
?>