<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ServiceResponse extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('mp/ServiceResponse', $data);
    }
}
?>