<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WalmartSellerAPI\Library;
use WalmartSellerAPI\util\XSDParser;

final class XSDTest extends TestCase {
    public function testInventory() {
        try {
            $feed = new WalmartSellerAPI\model\InventoryFeed();

            $feed['InventoryHeader'] = array(
                'version' => '1.4',
                'feedDate' => date('Y-m-dTH:i:s'),
            );

            $feed['inventory'] = array(
                array(
                    'sku' => 'test',
                    'quantity' => array(
                        'unit' => 'EACH',
                        'amount' => 1
                    )
                ),
                array(
                    'sku' => 'test2',
                    'quantity' => array(
                        'unit' => 'EACH',
                        'amount' => 12
                    )
                )
            );

            $xml = $feed->asXML();

            $feed = new WalmartSellerAPI\model\InventoryFeed($xml);
        
            $this->assertEquals($xml, $feed->asXML());
        } catch(\Exception $e) {
            error_log($e->getMessage());
            $this->fail();
        }
    }

    public function testProduct() {
        try {
			$types = XSDParser::parse('product/MPItemFeed');

            // print_r($types);
            // Library::load('product/MPItemFeed');
        
            $this->assertTrue(true);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }

    public function testShip() {
        try {
			$types = XSDParser::parse('orders/ShipConfirmRequestV3.3');

            // print_r($types);
            // Library::load('product/MPItemFeed');
        
            $this->assertTrue(true);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }

    public function testRefund() {
        try {
			$types = XSDParser::parse('orders/RefundRequestV3.3');

            // print_r($types);
            // Library::load('product/MPItemFeed');
        
            $this->assertTrue(true);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }

    public function testOrder() {
        try {
			$types = XSDParser::parse('orders/PurchaseOrderV3.3');

            // print_r($types);
            // Library::load('product/MPItemFeed');
        
            $this->assertTrue(true);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }

    public function testCancel() {
        try {
			$types = XSDParser::parse('orders/CancelRequestV3.3');

            // print_r($types);
            // Library::load('product/MPItemFeed');
        
            $this->assertTrue(true);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }

    public function testPrices() {
        try {
            $feed = new WalmartSellerAPI\model\BulkPriceFeed();

            $feed['PriceHeader'] = array(
                'version' => '1.5.1'
            );
            $feed['Price'] = array(
                'itemIdentifier' => array(
                    'sku' => 'sku-656666666'
                ),
                'pricingList' => array(
                    'pricing' => array(
                        'currentPrice' => array(
                            'value' => array(
                                'currency' => 'USD',
                                'amount' => '4.00'
                            )
                        ),
                        'comparisonPrice' => array(
                            'value' => array(
                                'amount' => '5.00'
                            ),
                        ),
                        'currentPriceType' => 'REDUCED'
                    )
                )
            );

            $xml = $feed->asXML();

            echo $xml;
            $feed = new WalmartSellerAPI\model\BulkPriceFeed($xml);
            echo $feed->asXML();
            $this->assertEquals($xml, $feed->asXML());
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->fail();
        }
    }
}
?>