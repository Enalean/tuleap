<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class XML_RNGValidator
{
    /**
     * @throws XML_ParseException
     */
    public function validate(SimpleXMLElement $xml_element, $rng_path)
    {
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
     *
     * @return \DOMDocument
     */
    private function simpleXmlElementToDomDocument(SimpleXMLElement $xml_element)
    {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom_element = $dom->importNode(dom_import_simplexml($xml_element), true);
        $dom->appendChild($dom_element);
        return $dom;
    }

    /**
     * @param             $rng_path
     * @throws XML_ParseException
     */
    private function extractErrors(DOMDocument $dom, $rng_path): void
    {
        $system_command = new System_Command();
        $temp           = tempnam(ForgeConfig::get('tmp_dir'), 'xml');
        $xml_file       = tempnam(ForgeConfig::get('tmp_dir'), 'xml_src_');

        try {
            file_put_contents($xml_file, $dom->saveXML());
            $indent = __DIR__ . '/../../utils/xml/indent.xsl';
            $system_command->exec(
                'xsltproc -o ' . escapeshellarg($temp) . ' ' . escapeshellarg($indent) . ' ' . escapeshellarg($xml_file)
            );
        } catch (System_Command_CommandException $ex) {
            unlink($temp);
            throw new \RuntimeException("Unable to generate pretty print version of XML file for error handling");
        }

        try {
            $jing   = __DIR__ . '/../../utils/xml/jing.jar';
            $system_command->exec('java -jar ' . escapeshellarg($jing) . ' ' .  escapeshellarg($rng_path) . ' ' . escapeshellarg($temp));
        } catch (System_Command_CommandException $ex) {
            $errors = [];
            foreach ($ex->getOutput() as $o) {
                $matches = array();
                if (preg_match('/:(\d+):(\d+):([^:]+):(.*)/', $o, $matches)) {
                    //1 line
                    //2 column
                    //3 type
                    //4 message
                    $errors[] = new XML_ParseError($matches[1], $matches[2], $matches[3], $matches[4]);
                }
            }
            throw new XML_ParseException($rng_path, $errors, file($temp, FILE_IGNORE_NEW_LINES));
        } finally {
            unlink($temp);
            unlink($xml_file);
        }
    }
}
