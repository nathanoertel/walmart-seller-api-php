<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\util\XSDParser;

class CategoryRequest {

    public function getCategories() {
        $type = XSDParser::load(array(
            'product/MPProduct',
            'product/category'
        ));

        $categories = array();

        return $this->getChoices($type);
    }

    private function getChoices($type, $options = array(), $path = array()) {
        $notFound = true;

        if(isset($type['_fields'])) {
            foreach($type['_fields'] as $field) {
                if(isset($field['type']) && $field['type'] == 'choice') {
                    foreach($field['options'] as $option) {
                        $currentPath = array_merge($path, array($option['name']));
                        $options = $this->getChoices($option, $options, $currentPath);
                        $notFound = false;
                    }
                }
            }
        }

        if($notFound) $options[] = $path;

        return $options;
    }

    public function getCategoryDetails($categories) {
        $type = XSDParser::load(array(
            'product/MPProduct',
            'product/category'
        ));

        $this->chooseCategory($categories, $type);

        return $type;
    }

    private function chooseCategory($categories, &$type) {
        $category = array_shift($categories);

        foreach($type['_fields'] as $index => $field) {
            if($field['type'] == 'choice') {
                foreach($field['options'] as $option) {
                    if($option['name'] == $category) {
                        if(!empty($categories)) $this->chooseCategory($categories, $option);
                        $type['_fields'][$index] = $option;
                    }
                }
            }
        }
    }
}