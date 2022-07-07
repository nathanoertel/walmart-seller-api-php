<?php
namespace WalmartSellerAPI\model;

use WalmartSellerAPI\model\AbstractModel;

class ItemResponses extends AbstractModel {

    protected function getXMLFromData($data) {
        if (is_string($data)) $xmlString = $data;
        else $xmlString = $data->asXML();
        return parent::getXMLFromData(str_replace(
            array(
                '<ItemResponse>',
                '</ItemResponse>',
                '<totalItems>',
                '</totalItems>',
                '<nextCursor>',
                '</nextCursor>'
            ),
            array(
                '<ns2:ItemResponse>',
                '</ns2:ItemResponse>',
                '<ns2:totalItems>',
                '</ns2:totalItems>',
                '<ns2:nextCursor>',
                '</ns2:nextCursor>'
            ),
            $xmlString
        ));
    }

    public function __construct($data = null) {
        parent::__construct(array(
            'responses/ItemResponse',
            'responses/ItemResponses'
        ), $data);
    }
}
?>