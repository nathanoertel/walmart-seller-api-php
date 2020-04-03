<?php
namespace WalmartSellerAPI;

class InventoryResponse extends AbstractResponse {
    protected function getModel($name) {
        switch($name) {
            case 'inventory':
                return 'WalmartSellerAPI\model\Inventory';
            default:
                throw new Exception('InventoryResponse '.$name.' Not Found');
        }
    }    
}