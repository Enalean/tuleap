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

    /**
     *
     * @param SimpleXMLElement $parent_node
     * @param string $node_name
     * @param string $node_value
     */
    public function insert(SimpleXMLElement $parent_node, $node_name, $node_value)
    {
        $node     = $parent_node->addChild($node_name);
        $dom_node = dom_import_simplexml($node);
        $document = $dom_node->ownerDocument;
        $value    = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($node_value);
        $cdata    = $document->createCDATASection($value);
        $dom_node->appendChild($cdata);
    }
}
