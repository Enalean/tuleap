<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use SimpleXMLElement;
use Tracker_FormElement_Container_Fieldset;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use XML_SimpleXMLCDATAFactory;

class FieldXmlExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_section_factory;

    /**
     * @var FieldNameFormatter
     */
    private $field_name_formatter;

    public function __construct(
        XML_SimpleXMLCDATAFactory $cdata_section_factory,
        FieldNameFormatter $field_name_formatter
    ) {
        $this->cdata_section_factory = $cdata_section_factory;
        $this->field_name_formatter  = $field_name_formatter;
    }

    public function exportFieldsetWithName(
        SimpleXMLElement $parent_node,
        string $name,
        string $label,
        int $rank,
        int $id
    ): SimpleXMLElement {
        $fieldset_node = $parent_node->addChild("formElement");
        $fieldset_node->addAttribute('type', Tracker_FormElement_Container_Fieldset::TYPE);

        $xml_id = "F" . $id;
        $fieldset_node->addAttribute('ID', $xml_id);
        $fieldset_node->addAttribute('rank', (string) $rank);
        $fieldset_node->addAttribute('use_it', '1');

        $this->cdata_section_factory->insert($fieldset_node, 'name', $name);
        $this->cdata_section_factory->insert($fieldset_node, 'label', $label);

        return $fieldset_node->addChild('formElements');
    }

    /**
     * @param array<string, string> $properties
     */
    public function exportField(
        \SimpleXMLElement $parent_node,
        string $type,
        string $name,
        string $label,
        string $jira_field_id,
        int $rank,
        bool $required,
        array $properties,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        $field = $parent_node->addChild('formElement');
        $field->addAttribute('type', $type);

        $xml_id = "F" . $jira_field_id;
        $field->addAttribute('ID', $xml_id);
        $field->addAttribute('rank', (string) $rank);
        $field->addAttribute('use_it', "1");

        if ($required) {
            $field->addAttribute('required', '1');
        }

        $formatted_name = $this->field_name_formatter->getFormattedName($name);
        $this->cdata_section_factory->insert($field, 'name', $formatted_name);

        $this->cdata_section_factory->insert($field, 'label', $label);

        foreach ($properties as $property_name => $property_value) {
            $properties_node = $field->addChild("properties");
            $properties_node->addAttribute($property_name, $property_value);
        }

        $jira_field_mapping_collection->addMapping(
            new FieldMapping(
                $jira_field_id,
                $xml_id,
                $formatted_name,
                $type
            )
        );
    }
}
