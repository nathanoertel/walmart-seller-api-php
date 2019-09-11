<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class Order extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct(array(
            'orders/PurchaseOrderV3.3',
            'orders/order'
        ), $data);
    }
}
?>