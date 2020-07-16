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

use Tracker_FormElement_Field_List_Bind_Users;
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

    /**
     * @param array<string, string> $properties
     * @param JiraFieldAPIAllowedValueRepresentation[] $bound_values
     */
    public function exportField(
        \SimpleXMLElement $container_parent_node,
        string $type,
        string $name,
        string $label,
        string $jira_field_id,
        int $rank,
        bool $required,
        array $properties,
        array $bound_values,
        FieldMappingCollection $jira_field_mapping_collection,
        ?string $bind_type
    ): void {
        $field = $container_parent_node->formElements->addChild('formElement');
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

        if ($bind_type === Tracker_FormElement_Field_List_Bind_Users::TYPE) {
            $bind_node = $field->addChild("bind");
            $bind_node->addAttribute("type", $bind_type);
            $items = $bind_node->addChild("items");
            $item  = $items->addChild('item');
            $item->addAttribute('label', Tracker_FormElement_Field_List_Bind_Users::REGISTERED_USERS_UGROUP_NAME);
        } elseif (count($bound_values) > 0) {
            $bind_node = $field->addChild("bind");
            $bind_node->addAttribute("type", \Tracker_FormElement_Field_List_Bind_Static::TYPE);
            $bind_node->addAttribute("is_rank_alpha", "0");

            $items_node = $bind_node->addChild("items");
            foreach ($bound_values as $value) {
                $item_node = $items_node->addChild("item");
                $item_node->addAttribute("ID", "V" . $value->getId());
                $item_node->addAttribute("label", $value->getName());
                $item_node->addAttribute("is_hidden", "0");
            }
        }

        $jira_field_mapping_collection->addMapping(
            new FieldMapping(
                $jira_field_id,
                $xml_id,
                $formatted_name,
                $type,
                $bind_type
            )
        );
    }
}
