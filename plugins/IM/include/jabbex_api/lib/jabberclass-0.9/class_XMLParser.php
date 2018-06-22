<?php
/*
 * This file was contributed (in part or whole) by a third party, and is
 * released under a BSD-compatible free software license.  Please see the
 * CREDITS and LICENSE sections below for details.
 * 
 *****************************************************************************
 *
 * DETAILS
 *
 * Provides the XML parser used by class_Jabber.php to parse Jabber XML data
 * received from the Jabber server.
 *
 *
 * CREDITS
 *
 * Originally by Hans Anderson (http://www.hansanderson.com/php/xml)
 * Adapted for class.jabber.php by Carlo Zottman (http://phpjabber.g-blog.net)
 * Adapted for class_Jabber.php by Steve Blinch (http://www.centova.com)
 *
 *
 * LICENSE
 *
 * xmlize() is by Hans Anderson, www.hansanderson.com/contact/
 *
 * Ye Ole "Feel Free To Use it However" License [PHP, BSD, GPL].
 * some code in xml_depth is based on code written by other PHPers
 * as well as one Perl script.  Poor programming practice and organization
 * on my part is to blame for the credit these people aren't receiving.
 * None of the code was copyrighted, though.
 *
 *
 * REFERENCE
 *
 * @ = attributes
 * # = nested tags
 * 
*/

class Jabbex_XMLParser {

	function __construct() {
		$this->valid = false;
	}
	
	// xmlize()
	// (c) Hans Anderson / http://www.hansanderson.com/php/xml/

	function xmlize($data) {
		$vals = $index = $array = array();
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);

    // XML_OPTION_SKIP_WHITE is disabled as it clobbers valid 
    // newlines in instant messages
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
		$this->valid = xml_parse_into_struct($parser, $data, $vals, $index);
		xml_parser_free($parser);

		$i = 0;

		$tagname = $vals[$i]['tag'];
		$array[$tagname]['@'] = empty($vals[$i]['attributes'])
			? array()
			: $vals[$i]['attributes'];
		$array[$tagname]['#'] = $this->_xml_depth($vals, $i);

		return $array;
	}



	// _xml_depth()
	// (c) Hans Anderson / http://www.hansanderson.com/php/xml/

	function _xml_depth($vals, &$i) {
		$children = array();

		if (!empty($vals[$i]['value'])) {
			array_push($children, trim($vals[$i]['value']));
		}

		while (++$i < count($vals)) {
			switch ($vals[$i]['type']) {
				case 'cdata':
					array_push($children, trim($vals[$i]['value']));
	 				break;

				case 'complete':
					$tagname = $vals[$i]['tag'];
					$size = empty($children[$tagname]) ? 0 : sizeof($children[$tagname]);
					$children[$tagname][$size]['#'] = array_key_exists('value',$vals[$i])
						? trim($vals[$i]['value']) : '';
					if (!empty($vals[$i]['attributes'])) {
						$children[$tagname][$size]['@'] = $vals[$i]['attributes'];
					}
					break;

				case 'open':
					$tagname = $vals[$i]['tag'];
					$size = empty($children[$tagname]) ? 0 : sizeof($children[$tagname]);
					if (!empty($vals[$i]['attributes'])) {
						$children[$tagname][$size]['@'] = $vals[$i]['attributes'];
					}
					$children[$tagname][$size]['#'] = $this->_xml_depth($vals, $i);
					break;

				case 'close':
					return $children;
					break;
			}
		}

		return $children;
	}



	// TraverseXMLize()
	// (c) acebone@f2s.com, a HUGE help!

	function TraverseXMLize($array, $arrName = "array", $level = 0) {
		if ($level == 0) {
			echo "<pre>";
		}

		while (list($key, $val) = @each($array)) {
			if (is_array($val)) {
				$this->TraverseXMLize($val, $arrName . "[" . $key . "]", $level + 1);
			} else {
				echo '$' . $arrName . '[' . $key . '] = "' . $val . "\"\n";
			}
		}

		if ($level == 0) {
			echo "</pre>";
		}
	}

}
?>