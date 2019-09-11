<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class FeedAcknowledgement extends AbstractModel {

    public function __construct($data = null) {
        parent::__construct('responses/FeedAcknowledgement', $data);
    }
}
?>