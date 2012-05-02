<?php

Class PacketMng{

	/*
	 * Receives a MUC configuration packet, fill it with the information
	 * in $fill_info, which is an array formated as follows:
	 * array( "field_1" => "value_1" , "field_2" => "value_2",...)
	 * Returns a response msg in xml format.
	 */
	function fill_muc_config_form(Array $packet, Array $fill_info){

		$ret_val_xml = "";
		
		$header = $packet["iq"]["@"];
		$nested_conf = $packet["iq"]["#"];
		$fields = $nested_conf["query"]["0"]["#"]["x"]["0"]["#"];
		unset($fields["title"]);
		unset($fields["instructions"]);
		unset($nested_conf["query"]["0"]["#"]["x"]["0"]["#"]);

		$header["type"] = "set";
		$to = $header["from"];
		$header["from"] = $header["to"];
		$header["to"] = $to;

		$nested_conf["query"]["0"]["#"]["x"]["0"]["@"]["type"] = "submit";

		foreach($fill_info as $var => $value){
			if( !$this->set_field_value($fields["field"], $var, $value) ) return false;
		}
		
//		var_dump($packet);
		
		// Building response array
		$response = array(
			"iq" => array(
				"@" => $header,
				"#" => $nested_conf
			)
		);
		
		$response["iq"]["#"]["query"]["0"]["#"]["x"]["0"]["#"] = $fields;
		
//		var_dump($response);
		
//		var_dump($header);
//		var_dump($nested_conf);
//		var_dump($fields);

		/***********************************
		 ******** Generate the XML
		 ***********************************/
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->setIndent(true);
        $xml->setIndentString(' ');

		// Starting roots (usualy we've got only 1 root for a pck).
		foreach($response as $key => $value){
			$xml->startElement($key);
			if(is_array($value)){
				// Attributes
				if( is_array($value["@"]) ){
					foreach($value["@"] as $attr => $attr_value){
						$xml->writeAttribute($attr,$attr_value);
					}
				}
				// Nested elements
				if( is_array($value["#"]) ){
					$this->write($xml,$value["#"]);
				}
			}
			$xml->endElement();
			$ret_val_xml = $xml->outputMemory(true);
		}
			
		return $ret_val_xml;

		//		var_dump($packet);
		//		var_dump($fill_info);
	}

	/*
	 * Return an element of the array $packet found through the path
	 * $path which is an array in the follwoing format ('first_index','second_index'...).
	 *
	 */
	function _node($packet,$path,$checkset = false) {
		$cursor = &$packet;

		$pathlength = count($path);
		for ($i=0; $i<$pathlength; $i++) {
			$last = ($i==$pathlength-1);

			$element = $path[$i];

			if (!is_array($cursor) || !isset($cursor[$element])) return ($checkset ? false : NULL);

			if ($last) {
				if ($checkset) {
					return isset($cursor[$element]);
				} else {
					return $cursor[$element];
				}
			} else {
				$cursor = &$cursor[$element];
			}

		}

		return ($checkset ? false : NULL);
	}

	/*
	 * Recursive function for writing nested XML elements.
	 */
	function write(XmlWriter $xml, $data){
		$current_element = null;
		foreach($data as $key => $value){
			$current_element = $key;
			if(is_array($value)){
				foreach($value as $index => $content){
					$xml->startElement($current_element);
					if(is_array($content)){
						// Attributes
						if( isset($content["@"]) && is_array($content["@"]) ){
							foreach($content["@"] as $attr => $attr_value){
								$xml->writeAttribute($attr,$attr_value);
							}
						}
						// Nested elements
						if( is_array($content["#"]) ){
							$this->write($xml,$content["#"]);
						}
						else{
							//$xml->writeElement($current_element, $content["#"]);
							$xml->text($content["#"]);
						}
					}
					$xml->endElement();
					//echo $xml->outputMemory(true);
				}

			}
		}
	}

	/*
	 * Searchs $var in a configuratio XML-array and sets its value to $value.
	 */
	function set_field_value(&$fields_array, $var, $value){
		foreach($fields_array as $key => $content){
			
			if( is_array($content) & is_array($content["@"]) ){
				// Check if we've got the right field
				if( isset($content["@"]["var"]) && !strcmp($content["@"]["var"], $var) ){
					// Clear field.
					unset($content["#"]);
					// Set the values
					if(is_array($value)){
						foreach($value as $val){
							$content["#"]["value"][] = array("#" => $val);
						}
					}
					else $content["#"]["value"][] = array("#" => $value);

					$fields_array[$key] = $content;
					return true;
				}

			}
		}
		// We didn't find the field
		return false;
	}

}
?>