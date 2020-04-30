<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use SimpleXMLElement;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use XML_SimpleXMLCDATAFactory;

class FieldChangeXMLExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $simplexml_cdata_factory)
    {
        $this->simplexml_cdata_factory = $simplexml_cdata_factory;
    }

    public function exportFieldChange(
        FieldMapping $mapping,
        SimpleXMLElement $changeset_node,
        SimpleXMLElement $node_submitted_on,
        string $value
    ): void {
        if ($mapping->getType() === Tracker_FormElement_Field_String::TYPE) {
            $this->exportStringFieldChange(
                $changeset_node,
                $mapping->getFieldName(),
                $value
            );
        } elseif ($mapping->getType() === Tracker_FormElement_Field_Text::TYPE) {
            $field_change_node = $changeset_node->addChild('field_change');
            $field_change_node->addAttribute('type', 'text');
            $field_change_node->addAttribute('field_name', $mapping->getFieldName());

            $this->simplexml_cdata_factory->insert(
                $field_change_node,
                'value',
                $value
            );
        } elseif ($mapping->getType() === Tracker_FormElement_Field_Float::TYPE) {
            $field_change_node = $changeset_node->addChild('field_change');
            $field_change_node->addAttribute('type', 'float');
            $field_change_node->addAttribute('field_name', $mapping->getFieldName());
            $field_change_node->addChild('value', $value);
        } elseif ($mapping->getType() === \Tracker_FormElement_Field_LastUpdateDate::TYPE) {
            $node_submitted_on[0] = $value;
        }
    }

    public function exportStringFieldChange(SimpleXMLElement $changeset_node, string $field_name, string $value): void
    {
        $field_change_node = $changeset_node->addChild('field_change');
        $field_change_node->addAttribute('type', Tracker_FormElement_Field_String::TYPE);
        $field_change_node->addAttribute('field_name', $field_name);

        $this->simplexml_cdata_factory->insert(
            $field_change_node,
            'value',
            $value
        );
    }
}
