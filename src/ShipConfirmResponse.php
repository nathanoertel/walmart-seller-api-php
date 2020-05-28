<?php
namespace WalmartSellerAPI;

class ShipConfirmResponse extends AbstractResponse {
    protected function getModel($name) {
        switch($name) {
            case 'order':
                return 'WalmartSellerAPI\model\Order';
            default:
                throw new \Exception('OrderAcknowledgementResponse '.$name.' Not Found');
        }
    }    
}