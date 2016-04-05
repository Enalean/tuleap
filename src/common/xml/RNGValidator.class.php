<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class XML_RNGValidator {

    public function validate(SimpleXMLElement $xml_element, $rng_path) {
        $dom          = $this->simpleXmlElementToDomDocument($xml_element);
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();
        $is_valid = @$dom->relaxNGValidate($rng_path);
        $xml_security->disableExternalLoadOfEntities();

        if (! $is_valid) {
            $this->extractErrors($dom, $rng_path);
        }
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

    private function extractErrors(DOMDocument $dom, $rng_path) {
        $indent   = ForgeConfig::get('codendi_utils_prefix') .'/xml/indent.xsl';
        $jing     = ForgeConfig::get('codendi_utils_prefix') .'/xml/jing.jar';
        $temp     = tempnam(ForgeConfig::get('tmp_dir'), 'xml');
        $xml_file = tempnam(ForgeConfig::get('tmp_dir'), 'xml_src_');
        file_put_contents($xml_file, $dom->saveXML());
        $cmd_indent = "xsltproc -o $temp $indent $xml_file";
        `$cmd_indent`;

        $output = array();
        $cmd_valid = "java -jar $jing $rng_path $temp";
        exec($cmd_valid, $output);
        $errors = array();
        foreach($output as $o) {
            $matches = array();
            preg_match('/:(\d+):(\d+):([^:]+):(.*)/', $o, $matches);
            //1 line
            //2 column
            //3 type
            //4 message
            $errors[] = new XML_ParseError($matches[1], $matches[2], $matches[3], $matches[4]);
        }
        throw new XML_ParseException($rng_path, $errors, file($temp, FILE_IGNORE_NEW_LINES));
    }
}
