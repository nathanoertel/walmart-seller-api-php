<?php
namespace WalmartSellerAPI;

class OrderAcknowledgementResponse extends AbstractXMLResponse {
    protected function getModel($name) {
        switch($name) {
            case 'order':
                return 'WalmartSellerAPI\model\Order';
            default:
                throw new Exception('OrderAcknowledgementResponse '.$name.' Not Found');
        }
    }    
}