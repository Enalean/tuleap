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
use Tracker_FormElementFactory;
use XML_SimpleXMLCDATAFactory;

class ContainersXMLCollectionBuilder
{
    public const DETAILS_FIELDSET_NAME    = 'details_fieldset';
    public const CUSTOM_FIELDSET_NAME     = 'custom_fieldset';
    public const ATTACHMENT_FIELDSET_NAME = 'attachment_fieldset';

    public const LEFT_COLUMN_NAME  = 'left_column';
    public const RIGHT_COLUMN_NAME = 'right_column';

    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $simplexml_cdata_factory)
    {
        $this->simplexml_cdata_factory = $simplexml_cdata_factory;
    }

    public function buildCollectionOfJiraContainersXML(SimpleXMLElement $form_elements): ContainersXMLCollection
    {
        $collection = new ContainersXMLCollection();

        $this->buildFieldsets($form_elements, $collection);
        $this->buildColumns($collection);

        return $collection;
    }

    private function buildFieldsets(SimpleXMLElement $form_elements, ContainersXMLCollection $collection): void
    {
        $details_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::DETAILS_FIELDSET_NAME,
            'Details',
            1,
            1
        );

        $custom_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::CUSTOM_FIELDSET_NAME,
            'Custom Fields',
            2,
            2
        );

        $attachment_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::ATTACHMENT_FIELDSET_NAME,
            'Attachments',
            3,
            3
        );

        $collection->addContainerInCollection(
            self::DETAILS_FIELDSET_NAME,
            $details_fieldset_node
        );

        $collection->addContainerInCollection(
            self::CUSTOM_FIELDSET_NAME,
            $custom_fieldset_node
        );

        $collection->addContainerInCollection(
            self::ATTACHMENT_FIELDSET_NAME,
            $attachment_fieldset_node
        );
    }

    private function buildColumns(ContainersXMLCollection $collection): void
    {
        $form_elements = $collection->getContainerByName(self::DETAILS_FIELDSET_NAME);

        $left_column   = $this->buildColumnXMLNode($form_elements->formElements, 'c1', 'c1', 1, 1);
        $right_column  = $this->buildColumnXMLNode($form_elements->formElements, 'c2', 'c2', 2, 2);

        $collection->addContainerInCollection(
            self::LEFT_COLUMN_NAME,
            $left_column
        );

        $collection->addContainerInCollection(
            self::RIGHT_COLUMN_NAME,
            $right_column
        );
    }

    private function buildColumnXMLNode(
        SimpleXMLElement $form_elements,
        string $name,
        string $label,
        int $rank,
        int $id
    ): SimpleXMLElement {
        $column_node = $form_elements->addChild("formElement");
        $column_node->addAttribute('type', Tracker_FormElementFactory::CONTAINER_COLUMN_TYPE);

        $xml_id = Tracker_FormElement::XML_ID_PREFIX . 'col' . $id;
        $column_node->addAttribute('ID', $xml_id);
        $column_node->addAttribute('rank', (string) $rank);

        $this->simplexml_cdata_factory->insert($column_node, 'name', $name);
        $this->simplexml_cdata_factory->insert($column_node, 'label', $label);

        $column_node->addChild('formElements');

        return $column_node;
    }

    private function buildFieldsetXMLNode(
        SimpleXMLElement $form_elements,
        string $name,
        string $label,
        int $rank,
        int $id
    ): SimpleXMLElement {
        $fieldset_node = $form_elements->addChild("formElement");
        $fieldset_node->addAttribute('type', Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE);

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
