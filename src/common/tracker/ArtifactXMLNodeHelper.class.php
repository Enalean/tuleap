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

class ArtifactXMLNodeHelper
{
    /** @var DOMDocument */
    private $document;

    public function __construct(DOMDocument $document)
    {
        $this->document = $document;
    }

    public function createElement($name)
    {
        return $this->document->createElement($name);
    }

    public function appendChild(DOMElement $node)
    {
        $this->document->appendChild($node);
    }

    public function addUserFormatAttribute(DOMElement $node, $is_anonymous)
    {
        $node->setAttribute('format', $is_anonymous ? 'email' : 'username');
        if ($is_anonymous) {
            $node->setAttribute('is_anonymous', "1");
        }
    }

    public function appendSubmittedBy(DOMElement $xml, $submitted_by, $is_anonymous)
    {
        $submitted_by_node = $this->document->createElement('submitted_by', $submitted_by);
        $this->addUserFormatAttribute($submitted_by_node, $is_anonymous);
        $xml->appendChild($submitted_by_node);
    }

    public function appendSubmittedOn(DOMElement $xml, $timestamp)
    {
        $xml->appendChild($this->getDateNodeFromTimestamp('submitted_on', $timestamp));
    }

    public function getDateNodeFromTimestamp($name, $timestamp)
    {
        $timestamp = intval($timestamp);
        $iso       = $timestamp > 0 ? date('c', $timestamp) : '';
        $node      = $this->document->createElement($name, $iso);
        $node->setAttribute('format', 'ISO8601');
        return $node;
    }

    public function getCDATASection(DOMNode $node, $value)
    {
        $no = $node->ownerDocument;
        if ($no === null) {
            return new DOMCdataSection('');
        }
        return $no->createCDATASection($value);
    }

    public function getNodeWithValue($node_name, $value)
    {
        $node = $this->document->createElement($node_name);
        $node->appendChild($this->getCDATASection($node, $value));
        return $node;
    }
}
