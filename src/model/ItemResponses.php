<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ItemResponses extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct(array(
            'responses/ItemResponse',
            'responses/ItemResponses'
        ), $data);
    }
}
?>