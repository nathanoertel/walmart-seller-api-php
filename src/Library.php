<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\util\XSDParser;
use WalmartSellerAPI\lib\Document;
use WalmartSellerAPI\lib\TypeDef;
use WalmartSellerAPI\lib\Type;

class Library {

	private static $loaded = array();

	private static $types = array();

	private static $documents = array();

	public static function load($name) {
		if(!isset(self::$loaded[$name])) {
			$parser = new XSDParser();

			$types = $parser->parse($name);

			if(isset($types['types'])) {
				foreach($types['types'] as $name => $t) {
					self::$types[$name] = new TypeDef($name, $t);
				}
			}
			
			if(isset($types['documents'])) {
				foreach($types['documents'] as $name => $t) {
					self::$documents[$name] = new Document($name, $t);
				}
			}

			self::$loaded[$name] = true;
		}
	}

	public static function getType($type) {
		if(isset(self::$types[$type])) {
			if(self::$types[$type]->isComplex()) return new Type(self::$types[$type]);
			else return self::$types[$type];
		} else {
			throw new \Exception("Type $type not found");
		}
	}

	public static function getDocument($document) {
		return self::$documents[$document];
	}
}
?>