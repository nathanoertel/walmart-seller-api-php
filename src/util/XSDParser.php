<?php
namespace WalmartSellerAPI\util;

class XSDParser {
	private static $parsed = array();

	private static $types = array();
	
	public static function load($type) {
		if(is_array($type)) {
			$element = $type[1];
			$doc = $type[0];
		} else {
			$element = $type;
			$doc = $type;
		}

		self::parse($doc);

		return self::$types[$element];
	}

	public static function parse($type, &$schema = array()) {
		if(!isset(self::$parsed[$type])) {
			$types = array();

			if(file_exists(dirname(__FILE__).'/../../xsd/v3/'.$type.'.xsd')) {
				$position = strrpos($type, '/');
				if($position === false) $namespace = '';
				else $namespace = substr($type, 0, $position).'/';

				self::$parsed[$type] = true;

				$doc = new \DOMDocument();

				$doc->load(dirname(__FILE__).'/../../xsd/v3/'.$type.'.xsd');

				foreach($doc->childNodes as $childNode) {
					self::parseNode('', $namespace, $childNode, $types, $schema);
				}
			} else {
				throw new \Exception('Type '.$type.' could not be found. ('.dirname(__FILE__).'/../../xsd/v3/'.$type.'.xsd'.')');
			}

			foreach($types as $t) $result = self::loadTypes($t);
		}
	}

	private static function loadTypes($type) {
		self::loadType(self::$types[$type]);

		return self::$types[$type];
	}

	private static function loadType(&$def, $recurse = true) {
		if(isset($def['_fields'])) {
			foreach($def['_fields'] as $index => $field) {
				if(isset($field['type']) && $field['type'] == 'choice') {
					foreach($field['options'] as $opt => $option) {
						self::loadType($def['_fields'][$index]['options'][$opt]);
					}
				} else {
					self::loadType($def['_fields'][$index]);
				}
			}
		} else {
			if(isset($def['type'])) {
				if(isset(self::$types[$def['type']])) {
					foreach(self::$types[$def['type']] as $index => $value) {
						$def[$index] = $value;
					}

					if($recurse) self::loadType($def, false);
				}
			}
		}

		if(isset($def['extends'])) {
			$def['_fields'] = array_merge(self::$types[$def['extends']]['_fields'], $def['_fields']);
		}
	}

	private static function parseNode($docNamespace, $namespace, $node, &$types, &$schema) {
		switch($node->nodeName) {
			case 'xsd:schema':
				$docNamespace = $node->attributes->getNamedItem('targetNamespace')->value;
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
				}
				break;
			case 'xsd:include':
				$name = $node->attributes->getNamedItem('schemaLocation')->value;
				self::parse($namespace.str_replace('.xsd', '', $name), $schema);
				break;
			case 'xsd:complexType':
				$name = $node->attributes->getNamedItem('name');

				if($name == null) {
					$elements = array();

					foreach($node->childNodes as $childNode) {
						self::parseNode($docNamespace, $namespace, $childNode, $types, $elements);
					}

					if(array_keys($elements) !== range(0, count($elements) - 1)) {
						foreach($elements as $index => $element) $schema[$index] = $element;
					} else {
						$schema['_fields'] = $elements;
					}
				} else {
					$name = $node->attributes->getNamedItem('name')->value;
					$elements = array();
					foreach($node->childNodes as $childNode) {
						self::parseNode($docNamespace, $namespace, $childNode, $types, $elements);
					}

					if(array_keys($elements) !== range(0, count($elements) - 1)) {
						if(isset(self::$types[$namespace.$name])) {
							self::$types[$namespace.$name] = array_merge(self::$types[$namespace.$name], $elements);
						} else self::$types[$namespace.$name] = $elements;
					} else {
						self::$types[$namespace.$name]['_fields'] = $elements;
					}
					self::$types[$namespace.$name]['namespace'] = $docNamespace;
					$types[] = $namespace.$name;
				}
				break;
			case 'xsd:sequence':
			case 'xsd:all':
				$fields = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $fields);
				}
				$schema['_fields'] = $fields;
				$schema['namespace'] = $docNamespace;
				break;
			case 'xsd:simpleContent':
				$extensions = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $extensions);
				}
				$schema['_fields'] = $extensions;
				$schema['namespace'] = $docNamespace;
				break;
			case 'xsd:extension':
				$base = $node->attributes->getNamedItem('base');

				if($base) {
					if($node->parentNode->nodeName == 'xsd:simpleContent') {
						$schema['_'] = array(
							'name' => '',
							'type' => (strpos($base->value, 'xsd:') === 0 ? '' : $namespace).substr($base->value, strpos($base->value, ':')+1)
						);
					} else {
						$schema['extends'] = $namespace.$base->value;
					}
				}

				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
				}
				break;
			case 'xsd:complexContent':
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
				}
				break;
			case 'xsd:element':
				$name = $node->attributes->getNamedItem('name');
				$type = $node->attributes->getNamedItem('type');
				$ref = $node->attributes->getNamedItem('ref');
				$minOccurs = $node->attributes->getNamedItem('minOccurs');
				if($name) $name = $name->value;
				$typeDef = array(
					'name' => $name
				);
				if($type) {
					if(strpos($type->value, ':') === false) $typeDef['type'] = $namespace.$type->value;
					else {
						$typeDef['type'] = (strpos($type->value, 'xsd:') === 0 ? '' : $namespace).substr($type->value, strpos($type->value, ':')+1);
					}
				} else if($ref) {
					if(strpos($ref->value, ':') === false) $typeDef['type'] = $namespace.$ref->value;
					else {
						$typeDef['type'] = (strpos($ref->value, 'xsd:') === 0 ? '' : $namespace).substr($ref->value, strpos($ref->value, ':')+1);
					}
				}
				
				if($minOccurs && intval($minOccurs->value) > 0) {
					$typeDef['required'] = true;
				} else $typeDef['required'] = false;

				$minOccurs = $node->attributes->getNamedItem('minOccurs');
				$maxOccurs = $node->attributes->getNamedItem('maxOccurs');

				if($minOccurs) $typeDef['minOccurs'] = $minOccurs->value;
				if($maxOccurs) $typeDef['maxOccurs'] = $maxOccurs->value;

				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $typeDef);
				}

				if($node->parentNode->nodeName == 'xsd:schema') {
					$typeDef['namespace'] = $docNamespace;
					self::$types[$namespace.$name] = $typeDef;
					$types[] = $namespace.$name;
				} else $schema[] = $typeDef;
				break;
			case 'xsd:annotation':
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
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
						self::parseNode($docNamespace, $namespace, $childNode, $types, $elements);
					}
					self::$types[$namespace.$name->value] = $elements;
					$types[] = $namespace.$name->value;
				} else {
					foreach($node->childNodes as $childNode) {
						self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
					}
				}
				break;
			case 'xsd:attribute':
				$name = $node->attributes->getNamedItem('name');
				$type = $node->attributes->getNamedItem('type');

				$field = array();

				if($name) $field['name'] = $name->value;
				if($type) {
					$field['type'] = (strpos($type->value, 'xsd:') === 0 ? '' : $namespace).substr($type->value, strpos($type->value, ':')+1);
				} else {
					$elements = array();
					foreach($node->childNodes as $childNode) {
						self::parseNode($docNamespace, $namespace, $childNode, $types, $elements);
					}
					$field = $elements;
				}
				$field['attribute'] = true;

				$schema[] = $field;
				break;
			case 'xsd:restriction':
				$name = str_replace('xsd:', '', $node->attributes->getNamedItem('base')->value);
				$schema['type'] = $name;
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $schema);
				}
				break;
			case 'xsd:enumeration':
				$schema['options'][] = $node->attributes->getNamedItem('value')->value;
				break;
			case 'xsd:choice':
				$options = array();
				foreach($node->childNodes as $childNode) {
					self::parseNode($docNamespace, $namespace, $childNode, $types, $options);
				}
				$minOccurs = intval($node->attributes->getNamedItem('minOccurs')->value);
				$maxOccurs = intval($node->attributes->getNamedItem('maxOccurs')->value);

				$schema[] = array(
					'type' => 'choice',
					'options' => $options,
					'required' => $minOccurs > 0,
					'max' => $maxOccurs
				);
				break;
			default:
				if(strpos($node->nodeName, 'xsd:') === 0 && isset($schema[str_replace('xsd:', '', $node->nodeName)])) {
					$schema[str_replace('xsd:', '', $node->nodeName)] = $node->attributes->getNamedItem('value')->value;
				}
				break;
		}
	}
}