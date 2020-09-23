<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_BindDecorator;
use XML_SimpleXMLCDATAFactory;

class BindStaticXmlExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_section_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $cdata_section_factory)
    {
        $this->cdata_section_factory = $cdata_section_factory;
    }

    /**
     * @param Tracker_FormElement_Field_List_Bind_StaticValue[] $values
     * @param Tracker_FormElement_Field_List_BindDecorator[]|null $decorators
     * @param array|null $default_values
     */
    public function exportToXml(
        \SimpleXMLElement $root,
        array $values,
        ?array $decorators,
        ?array $default_values,
        array &$xml_mapping
    ): void {
        $child = $root->addChild('items');

        foreach ($values as $value) {
            $this->exportValueAsXml(
                $child,
                $value->getXMLId(),
                (string) $value->getId(),
                $value->getLabel(),
                (bool) $value->isHidden(),
                $value->getDescription(),
                $xml_mapping
            );
        }

        if ($decorators) {
            $child = $root->addChild('decorators');
            foreach ($decorators as $deco) {
                $decorator = array_search($deco->value_id, $xml_mapping['values']);
                if ($decorator) {
                    $deco->exportToXML($child, $decorator);
                } else {
                    $deco->exportNoneToXML($child);
                }
            }
        }

        if ($default_values) {
            $default_child = $root->addChild('default_values');
            foreach ($default_values as $id => $nop) {
                if ($ref = array_search($id, $xml_mapping['values'])) {
                    $default_child->addChild('value')->addAttribute('REF', $ref);
                }
            }
        }
    }

    private function exportValueAsXml(
        \SimpleXMLElement $child,
        string $ID,
        string $id,
        string $label,
        bool $is_hidden,
        string $description,
        array &$xml_mapping
    ): void {
        $grandchild = $child->addChild('item');
        $grandchild->addAttribute('ID', $ID);
        $xml_mapping['values'][$ID] = $id;
        $grandchild->addAttribute('label', $label);
        $grandchild->addAttribute('is_hidden', $is_hidden ? "1" : "0");

        if ($description !== '') {
            $this->cdata_section_factory->insert($grandchild, 'description', $description);
        }
    }
}
