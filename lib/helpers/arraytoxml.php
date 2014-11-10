<?php
class ArrayToXML
{
	/**
	 * The main function for converting to an XML document.
	 * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	 *
	 * @param array $data
	 * @param string $rootNodeName - what you want the root node to be - defaultsto data.
	 * @param SimpleXMLElement $xml - should only be used recursively
	 * @return string XML
	 */
	public function buildXml($file = null){
		
		if($file != null){
			$xml = "<?xml version='1.0' encoding='utf-8'?>\n<data>\n";
			$xml .= file_get_contents($file);			
			
			
			//$xml .= simplexml_load_file($file,null, LIBXML_NOCDATA);
			$xml .= "\n".'</data>';
			$c = simplexml_load_string($xml);
			$a = $c->asXML();
			
			return $xml;			
		}else{
			return 'No file specified' ;
		}
	}
	
	public function generate_xml_from_array($array, $node_name) {
		$xml = '';

		if (is_array($array) || is_object($array)) {
			foreach ($array as $key=>$value) {
				if (is_numeric($key)) {
					$key = $node_name;
				}

				$xml .= '<' .  str_replace(' ', '-',strtolower($key)) . '>' . $this->generate_xml_from_array($value, $node_name) . '</' .  str_replace(' ', '-',strtolower($key)) . '>' . "\n";
			}
		} else {
			$xml = htmlspecialchars($array, ENT_QUOTES) ;
		}

		return $xml;
	}
	
	public function generate_valid_xml_from_array($array, $node_block='nodes', $node_name='node') {
		$xml .= '<' . str_replace(' ', '-',strtolower($node_block)) . '>'. "\n" ;
		$xml .=  $this->generate_xml_from_array($array, $node_name);
		$xml .= '</' .  str_replace(' ', '-',strtolower($node_block)) . '>'. "\n" ;

		return $xml;
	}
	
	public static function toXml($data, $rootNodeName = 'data', $xml=null)
	{
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}
 
		if ($xml == null)
		{
			$xml = simplexml_load_string("<$rootNodeName />");
		}
 
		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				$key = "unknownNode_". (string) $key;
			}
 
			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z]/i', '', $key);
 
			// if there is another array found recrusively call this function
			if (is_array($value))
			{
				$node = $xml->addChild($key);
				// recrusive call.
				ArrayToXML::toXml($value, $rootNodeName, $node);
			}
			else 
			{
				// add single node.
                                $value = htmlentities($value);
				$xml->addChild($key,$value);
			}
 
		}
		// pass back as string. or simple xml object if you want!
		return $xml;
	}
}

?>