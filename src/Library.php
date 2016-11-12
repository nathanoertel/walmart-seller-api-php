<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\Utility\XSDParser;
use WalmartSellerAPI\Type\CommonType;

class Library {

	private static $types = array();

	private static $documents = array();

	public static function load($name) {
		$types = XSDParser::parse($name);

		foreach($types['types'] as $name => $t) {
			self::$types[$name] = $t;
		}

		foreach($types['documents'] as $name => $t) {
			self::$documents[$name] = $t;
		}
	}

	public static function getType($type) {
		if(isset(self::$types[$type]['_fields']) || isset(self::$types[$type]['_elements'])) return new CommonType($type, self::$types[$type]);
		else return self::$types[$type];
	}

	public static function getDocument($document) {
		return self::$documents[$document];
	}
}
?>