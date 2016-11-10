<?php
namespace WalmartSellerAPI\Utility;

class XSDParser {

	private static $parsed = array();
	
	public static function parse($type, &$schema = array()) {
		if(!isset(self::$parsed[$type])) {
			if(file_exists(dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd')) {
				$position = strrpos($type, '/');
				if($position === false) $namespace = '';
				else $namespace = substr($type, 0, $position).'/';

				$doc = new \DOMDocument();

				$doc->load(dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd');

				foreach($doc->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $schema);
				}

				self::$parsed[$type] = true;
				} else {
				throw new \Exception('Type '.$type.' could not be found. ('.dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd'.')');
			}

			return $schema;
		}

		return true;
	}

	private static function parseNode($namespace, $node, &$schema) {
		switch($node->nodeName) {
			case 'xsd:schema':
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $schema);
				}
				return $schema;
			case 'xsd:include':
				$name = $node->attributes->getNamedItem('schemaLocation')->value;
				self::parse($namespace.str_replace('.xsd', '', $name), $schema);
				break;
			case 'xsd:complexType':
				$name = $node->attributes->getNamedItem('name');

				if($name == null) {
					foreach($node->childNodes as $childNode) {
						self::parseNode($namespace, $childNode, $schema);
					}
				} else {
					$name = $node->attributes->getNamedItem('name')->value;
					$elements = array();
					foreach($node->childNodes as $childNode) {
						self::parseNode($namespace, $childNode, $elements);
						$schema['types'][$namespace.$name] = $elements;
					}
				}
				break;
			case 'xsd:sequence':
				$fields = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $fields);
				}
				$schema['_fields'] = $fields;
				break;
			case 'xsd:element':
				$name = $node->attributes->getNamedItem('name')->value;
				$type = $node->attributes->getNamedItem('type');
				$minOccurs = $node->attributes->getNamedItem('minOccurs');
				$typeDef = array();
				if($type) {
					if(strpos($type->value, 'xsd:') === 0) $typeDef['type'] = str_replace('xsd:', '', $type->value);
					else $typeDef['type'] = $namespace.$type->value;
				}
				
				if($minOccurs && intval($minOccurs->value) > 0) {
					$typeDef['required'] = true;
				} else $typeDef['required'] = false;

				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $typeDef);
				}

				if($node->parentNode->nodeName == 'xsd:schema') $schema['documents'][$name] = $typeDef;
				else $schema[$name] = $typeDef;
				break;
			case 'xsd:annotation':
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $schema);
				}
				break;
			case 'xsd:documentation':
				$schema['documentation'] = $node->textContent;
				break;
			case 'xsd:appinfo':
				// $schema['appinfo'] = array();
				// foreach($node->childNodes as $childNode) {
				// 	if($childNode->attributes) {
				// 		echo $childNode->nodeName."\n";
				// 		$schema['appinfo'][$childNode->nodeName] = $childNode->attributes->getNamedItem('value')->value;
				// 	}
				// }
				break;
			case 'xsd:simpleType':
				$name = $node->attributes->getNamedItem('name');
				if($name) {
					$elements = array();
					foreach($node->childNodes as $childNode) {
						self::parseNode($namespace, $childNode, $elements);
					}
					$schema['types'][$namespace.$name->value] = $elements;
				} else {
					foreach($node->childNodes as $childNode) {
						self::parseNode($namespace, $childNode, $schema);
					}
				}
				break;
			case 'xsd:attribute':
				$name = $node->attributes->getNamedItem('name');
				$elements = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $elements);
					$schema[$name->value] = $elements;
					$schema[$name->value]['attribute'] = true;
				}
				break;
			case 'xsd:restriction':
				$name = str_replace('xsd:', '', $node->attributes->getNamedItem('base')->value);
				$schema['type'] = $name;
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $schema);
				}
				break;
			case 'xsd:enumeration':
				$schema['options'][] = $node->attributes->getNamedItem('value')->value;
				break;
			case 'xsd:choice':
				$options = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($namespace, $childNode, $options);
				}
				$minOccurs = intval($node->attributes->getNamedItem('minOccurs')->value);
				$maxOccurs = intval($node->attributes->getNamedItem('maxOccurs')->value);

				$schema['_elements'] = array(
					'elements' => $options,
					'required' => $minOccurs > 0,
					'max' => $maxOccurs
				);
				break;
			default:
				if(strpos($node->nodeName, 'xsd:') === 0) {
					if($node->attributes->getNamedItem('value') == null) echo $node->nodeName;
					$schema[str_replace('xsd:', '', $node->nodeName)] = $node->attributes->getNamedItem('value')->value;
				}
				break;
		}
	}
}