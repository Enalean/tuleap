<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use SimpleXMLElement;
use Tracker_FormElement;
use Tracker_FormElement_Container_Fieldset;
use XML_SimpleXMLCDATAFactory;

class ContainersXMLCollectionBuilder
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $simplexml_cdata_factory)
    {
        $this->simplexml_cdata_factory = $simplexml_cdata_factory;
    }

    public function buildCollectionOfFieldsetsXML(SimpleXMLElement $form_elements): ContainersXMLCollection
    {
        $collection = new ContainersXMLCollection();

        $node_jira_atf_form_elements = $this->buildFieldsetXMLNode(
            $form_elements,
            'jira_atf',
            'Jira ATF',
            0,
            1
        );

        $collection->addFieldsetInCollection(
            'atf',
            $node_jira_atf_form_elements
        );

        $node_jira_custom_form_elements = $this->buildFieldsetXMLNode(
            $form_elements,
            'jira_custom',
            'Jira Custom Fields',
            0,
            2
        );

        $collection->addFieldsetInCollection(
            'custom',
            $node_jira_custom_form_elements
        );

        return $collection;
    }

    private function buildFieldsetXMLNode(
        SimpleXMLElement $form_elements,
        string $name,
        string $label,
        int $rank,
        int $id
    ): SimpleXMLElement {
        $fieldset_node = $form_elements->addChild("formElement");
        $fieldset_node->addAttribute('type', Tracker_FormElement_Container_Fieldset::TYPE);

        $xml_id = Tracker_FormElement::XML_ID_PREFIX . $id;
        $fieldset_node->addAttribute('ID', $xml_id);
        $fieldset_node->addAttribute('rank', (string) $rank);
        $fieldset_node->addAttribute('use_it', '1');

        $this->simplexml_cdata_factory->insert($fieldset_node, 'name', $name);
        $this->simplexml_cdata_factory->insert($fieldset_node, 'label', $label);

        $fieldset_node->addChild('formElements');

        return $fieldset_node;
    }
}
