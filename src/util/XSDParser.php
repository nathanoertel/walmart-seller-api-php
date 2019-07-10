<?php
namespace WalmartSellerAPI\util;

class XSDParser {

	private $parsed = array();
	
	public function parse($type, &$schema = array()) {
		if(!isset($this->parsed[$type])) {
			if(file_exists(dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd')) {
				$position = strrpos($type, '/');
				if($position === false) $namespace = '';
				else $namespace = substr($type, 0, $position).'/';

				$doc = new \DOMDocument();

				$doc->load(dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd');

				foreach($doc->childNodes as $childNode) {
					$this->parseNode('', $namespace, $childNode, $schema);
				}

				$this->parsed[$type] = true;
			} else {
				throw new \Exception('Type '.$type.' could not be found. ('.dirname(__FILE__).'/../../xsd/WalmartMarketplaceXSDs-2.1.6/'.$type.'.xsd'.')');
			}

			return $schema;
		}

		return true;
	}

	private function parseNode($docNamespace, $namespace, $node, &$schema) {
		switch($node->nodeName) {
			case 'xsd:schema':
				$docNamespace = $node->attributes->getNamedItem('targetNamespace')->value;
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $schema);
				}
				return $schema;
			case 'xsd:include':
				$name = $node->attributes->getNamedItem('schemaLocation')->value;
				$this->parse($namespace.str_replace('.xsd', '', $name), $schema);
				break;
			case 'xsd:complexType':
				$name = $node->attributes->getNamedItem('name');

				if($name == null) {
					foreach($node->childNodes as $childNode) {
						$this->parseNode($docNamespace, $namespace, $childNode, $schema);
					}
				} else {
					$name = $node->attributes->getNamedItem('name')->value;
					$elements = array();
					foreach($node->childNodes as $childNode) {
						$this->parseNode($docNamespace, $namespace, $childNode, $elements);
					}
					$schema['types'][$namespace.$name] = $elements;
					$schema['types'][$namespace.$name]['namespace'] = $docNamespace;
				}
				break;
			case 'xsd:sequence':
				$fields = array();
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $fields);
				}
				$schema['_fields'] = $fields;
				$schema['namespace'] = $docNamespace;
				break;
			case 'xsd:simpleContent':
				$extensions = array();
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $extensions);
				}
				$schema['_fields'] = $extensions;
				$schema['namespace'] = $docNamespace;
				break;
			case 'xsd:extension':
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $schema);
				}
				break;
			case 'xsd:element':
				$name = $node->attributes->getNamedItem('name')->value;
				$type = $node->attributes->getNamedItem('type');
				$minOccurs = $node->attributes->getNamedItem('minOccurs');
				$typeDef = array();
				if($type) {
					if(strpos($type->value, ':') === false) $typeDef['type'] = $namespace.$type->value;
					else {
						$typeDef['type'] = (strpos($type->value, 'xsd:') === 0 ? '' : $namespace).substr($type->value, strpos($type->value, ':')+1);
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
					$this->parseNode($docNamespace, $namespace, $childNode, $typeDef);
				}

				if($node->parentNode->nodeName == 'xsd:schema') {
					$typeDef['namespace'] = $docNamespace;
					$schema['documents'][$name] = $typeDef;
				} else $schema[$name] = $typeDef;
				break;
			case 'xsd:annotation':
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $schema);
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
						$this->parseNode($docNamespace, $namespace, $childNode, $elements);
					}
					$schema['types'][$namespace.$name->value] = $elements;
				} else {
					foreach($node->childNodes as $childNode) {
						$this->parseNode($docNamespace, $namespace, $childNode, $schema);
					}
				}
				break;
			case 'xsd:attribute':
				$name = $node->attributes->getNamedItem('name');
				$type = $node->attributes->getNamedItem('type');

				if($type) {
					$schema[$name->value]['type'] = (strpos($type->value, 'xsd:') === 0 ? '' : $namespace).substr($type->value, strpos($type->value, ':')+1);
				} else {
					$elements = array();
					foreach($node->childNodes as $childNode) {
						$this->parseNode($docNamespace, $namespace, $childNode, $elements);
					}
					$schema[$name->value] = $elements;
				}
				$schema[$name->value]['attribute'] = true;
				break;
			case 'xsd:restriction':
				$name = str_replace('xsd:', '', $node->attributes->getNamedItem('base')->value);
				$schema['type'] = $name;
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $schema);
				}
				break;
			case 'xsd:enumeration':
				$schema['options'][] = $node->attributes->getNamedItem('value')->value;
				break;
			case 'xsd:choice':
				$options = array();
				foreach($node->childNodes as $childNode) {
					$this->parseNode($docNamespace, $namespace, $childNode, $options);
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
				if(strpos($node->nodeName, 'xsd:') === 0 && isset($schema[str_replace('xsd:', '', $node->nodeName)])) {
					$schema[str_replace('xsd:', '', $node->nodeName)] = $node->attributes->getNamedItem('value')->value;
				}
				break;
		}
	}
}