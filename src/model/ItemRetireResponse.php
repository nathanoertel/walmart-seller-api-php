<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ItemRetireResponse extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('responses/ItemRetireResponse', $data);
    }
}
?>