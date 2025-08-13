<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker;

use SimpleXMLElement;
use Tracker_FormElement;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;

class TrackerXMLFieldMappingFromExistingTracker
{
    /**
     * @var array
     */
    private $xml_fields_mapping = [];

    /**
     * @param Tracker_FormElement[] $form_elements_existing
     * @return array
     */
    public function getXmlFieldsMapping(\SimpleXMLElement $xml_tracker, array $form_elements_existing)
    {
        $all_form_elements = [];
        $this->browseEachFormElementsFromXML($xml_tracker->formElements, $all_form_elements);

        foreach ($form_elements_existing as $form_element_existing) {
            if (isset($all_form_elements[$form_element_existing->getName()])) {
                $this->addFormElementsInFieldMapping($all_form_elements[$form_element_existing->getName()], $form_element_existing);
            }
        }
        return $this->xml_fields_mapping;
    }

    /**
     * @param SimpleXMLElement[] &$all_form_elements
     */
    private function browseAllFormElementFromXML(SimpleXMLElement $xml_form_element, array &$all_form_elements)
    {
        $all_form_elements[(string) $xml_form_element->name] = $xml_form_element;

        if (! isset($xml_form_element->formElements)) {
            return;
        }

        foreach ($xml_form_element->formElements as $xml_form_elements) {
            $this->browseEachFormElementsFromXML($xml_form_elements, $all_form_elements);
        }
    }

    /**
     * @param SimpleXMLElement[] &$all_form_elements
     */
    private function browseEachFormElementsFromXML(SimpleXMLElement $xml_form_elements, array &$all_form_elements)
    {
        foreach ($xml_form_elements->formElement as $xml_form_element) {
            $this->browseAllFormElementFromXML($xml_form_element, $all_form_elements);
        }
    }

    private function addFormElementsInFieldMapping(SimpleXMLElement $form_element_xml, Tracker_FormElement $form_element_existing)
    {
        $form_element_id                            = (string) $form_element_xml->attributes()['ID'];
        $this->xml_fields_mapping[$form_element_id] = $form_element_existing;

        if ($form_element_existing instanceof SelectboxField && isset($form_element_xml->bind)) {
            $this->addBindInFieldMapping($form_element_xml, $form_element_existing);
        }
    }

    private function addBindInFieldMapping(SimpleXMLElement $form_element_xml, SelectboxField $form_element_existing)
    {
        $items = [];
        if (isset($form_element_xml->bind->items->item)) {
            foreach ($form_element_xml->bind->items->item as $item) {
                $items[(string) $item->attributes()['label']] = (string) $item->attributes()['ID'];
            }
        }
        foreach ($form_element_existing->getBind()->getAllValues() as $bind) {
            if (isset($items[$bind->getLabel()])) {
                    $bind_id                            = $items[$bind->getLabel()];
                    $this->xml_fields_mapping[$bind_id] = $bind;
            }
        }
    }
}
