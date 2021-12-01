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
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;

class ContainersXMLCollectionBuilder
{
    public const DETAILS_FIELDSET_NAME    = 'details_fieldset';
    public const CUSTOM_FIELDSET_NAME     = 'custom_fieldset';
    public const ATTACHMENT_FIELDSET_NAME = 'attachment_fieldset';
    public const LINKS_FIELDSET_NAME      = 'links_fieldset';

    public const LEFT_COLUMN_NAME  = 'left_column';
    public const RIGHT_COLUMN_NAME = 'right_column';

    public function buildCollectionOfJiraContainersXML(SimpleXMLElement $form_elements, ContainersXMLCollection $collection): void
    {
        $this->buildFieldsets($form_elements, $collection);
        $this->buildColumns($collection);
    }

    private function buildFieldsets(SimpleXMLElement $form_elements, ContainersXMLCollection $collection): void
    {
        $details_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::DETAILS_FIELDSET_NAME,
            'Details',
            1,
            $collection->getNextId(),
        );

        $custom_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::CUSTOM_FIELDSET_NAME,
            'Custom Fields',
            2,
            $collection->getNextId(),
        );

        $attachment_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::ATTACHMENT_FIELDSET_NAME,
            'Attachments',
            3,
            $collection->getNextId(),
        );

        $lins_fieldset_node = $this->buildFieldsetXMLNode(
            $form_elements,
            self::LINKS_FIELDSET_NAME,
            'Links',
            4,
            $collection->getNextId(),
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

        $collection->addContainerInCollection(
            self::LINKS_FIELDSET_NAME,
            $lins_fieldset_node,
        );
    }

    private function buildColumns(ContainersXMLCollection $collection): void
    {
        $form_elements = $collection->getContainerByName(self::DETAILS_FIELDSET_NAME);

        $left_column  = $this->buildColumnXMLNode($form_elements->formElements, 'c1', 'c1', 1, $collection->getNextId());
        $right_column = $this->buildColumnXMLNode($form_elements->formElements, 'c2', 'c2', 2, $collection->getNextId());

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
        int $id,
    ): SimpleXMLElement {
        $column = (new XMLColumn(Tracker_FormElement::XML_ID_PREFIX . 'col' . $id, $name))
            ->withRank($rank)
            ->withLabel($label);

        return $column->export($form_elements);
    }

    private function buildFieldsetXMLNode(
        SimpleXMLElement $form_elements,
        string $name,
        string $label,
        int $rank,
        int $id,
    ): SimpleXMLElement {
        $xml_fieldset = (new XMLFieldset(Tracker_FormElement::XML_ID_PREFIX . $id, $name))
            ->withRank($rank)
            ->withLabel($label);

        return $xml_fieldset->export($form_elements);
    }
}
