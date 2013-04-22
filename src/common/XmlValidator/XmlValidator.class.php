<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class XmlValidator {

    public function __construct() {}

    /**
     *
     * @return boolean
     */
    public function nodeIsValid(SimpleXMLElement $xml_node, $rng_path) {
        $dom = $this->simpleXmlElementToDomDocument($xml_node);
        return $dom->relaxNGValidate($rng_path);
    }

    /**
     * Create a dom document based on a SimpleXMLElement
     *
     * @param SimpleXMLElement $xml_element
     *
     * @return \DOMDocument
     */
    private function simpleXmlElementToDomDocument(SimpleXMLElement $xml_element) {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom_element = $dom->importNode(dom_import_simplexml($xml_element), true);
        $dom->appendChild($dom_element);
        return $dom;
    }
}

?>
