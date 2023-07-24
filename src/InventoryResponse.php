<?php
namespace WalmartSellerAPI;

class InventoryResponse extends AbstractXMLResponse {
    protected function getModel($name) {
        switch($name) {
            case 'inventory':
                return 'WalmartSellerAPI\model\Inventory';
            default:
                throw new \Exception('InventoryResponse '.$name.' Not Found');
        }
    }    
}