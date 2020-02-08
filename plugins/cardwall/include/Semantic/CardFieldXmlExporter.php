<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Cardwall_Semantic_CardFields;
use SimpleXMLElement;
use Tracker;

class CardFieldXmlExporter
{
    /**
     * @var BackgroundColorDao
     */
    private $color_dao;

    public function __construct(BackgroundColorDao $color_dao)
    {
        $this->color_dao = $color_dao;
    }

    /**
     * @param array                        $xml_mapping
     */
    public function exportToXml(SimpleXMLElement $root, array $xml_mapping, Cardwall_Semantic_CardFields $semantic)
    {
        $child = $root->addChild('semantic');
        $this->extractCardFields($child, $xml_mapping, $semantic->getFields());
        $this->extractBackgroundColor($child, $xml_mapping, $semantic->getTracker());
    }

    /**
     * @param                               $xml_mapping
     * @param  \Tracker_FormElement_Field[] $fields
     */
    private function extractCardFields(SimpleXMLElement $semantic, $xml_mapping, array $fields)
    {
        $semantic->addAttribute('type', Cardwall_Semantic_CardFields::NAME);
        foreach ($fields as $field) {
            if (in_array($field->getId(), $xml_mapping)) {
                $semantic->addChild('field')->addAttribute('REF', array_search($field->getId(), $xml_mapping));
            }
        }
    }

    private function extractBackgroundColor(SimpleXMLElement $semantic, $xml_mapping, Tracker $tracker)
    {
        $field_id = $this->color_dao->searchBackgroundColor($tracker->getId());
        if (in_array($field_id, $xml_mapping)) {
            $semantic->addChild('background-color')->addAttribute('REF', array_search($field_id, $xml_mapping));
        }
    }
}
