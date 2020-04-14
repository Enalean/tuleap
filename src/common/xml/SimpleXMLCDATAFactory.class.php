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

/**
 * I create CDATA sections for SimpleXMLElement objects
 *
 * It ensures that sections are xml compatible by enforcing/converting the encoding
 */
class XML_SimpleXMLCDATAFactory
{

    public function insert(SimpleXMLElement $parent_node, string $node_name, $node_value): SimpleXMLElement
    {
        $node = $parent_node->addChild($node_name);
        $this->addCDATAContentToXMLNode($node, $node_value);

        return $node;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function insertWithAttributes(
        SimpleXMLElement $parent_node,
        string $node_name,
        string $node_value,
        array $attributes
    ): SimpleXMLElement {
        $node = $parent_node->addChild($node_name);
        foreach ($attributes as $name => $value) {
            $node->addAttribute((string) $name, (string) $value);
        }

        $this->addCDATAContentToXMLNode($node, $node_value);

        return $node;
    }

    private function addCDATAContentToXMLNode(SimpleXMLElement $node, $node_value): void
    {
        $dom_node = dom_import_simplexml($node);
        $document = $dom_node->ownerDocument;
        if ($document === null) {
            return;
        }
        $value    = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($node_value);
        $cdata    = $document->createCDATASection($value);
        $dom_node->appendChild($cdata);
    }
}
