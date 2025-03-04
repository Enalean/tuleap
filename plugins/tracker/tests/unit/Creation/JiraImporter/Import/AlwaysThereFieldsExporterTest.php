<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\XML\XMLTracker;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AlwaysThereFieldsExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsAFieldset(): void
    {
        $builder = new AlwaysThereFieldsExporter();

        $id_generator = new FieldAndValueIDGenerator();

        $status_value_collection = $this->createMock(StatusValuesCollection::class);
        $status_value_collection->method('getAllValues')->willReturn([
            JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => '10000', 'name' => 'A new state'], $id_generator),
            JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => '3', 'name' => 'In progress'], $id_generator),
            JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => '4', 'name' => 'Fini'], $id_generator),
        ]);

        $field_mapping_collection = new FieldMappingCollection();
        $xml_tracker              = $builder->exportFields(new XMLTracker($id_generator, 'bug'), $status_value_collection, $field_mapping_collection);

        $xml = $xml_tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><trackers />'));

        $custom_elements_fieldset = $xml->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]')[0];
        assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, $custom_elements_fieldset['type'], 'There is a fieldset named customelement');

        $custom_elements_fieldset = $xml->xpath('//formElement[name="' . AlwaysThereFieldsExporter::LEFT_COLUMN_NAME . '"]')[0];
        assertEquals(\Tracker_FormElementFactory::CONTAINER_COLUMN_TYPE, $custom_elements_fieldset['type'], 'There is a column named left column');

        $artifact_id_field = $xml->xpath('//formElement[name="' . AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME . '"]//formElement[name="' . AlwaysThereFieldsExporter::JIRA_ARTIFACT_ID_FIELD_ID . '"]')[0];
        assertEquals(\Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE, $artifact_id_field['type'], 'There is an artifact_id field in the right column');

        $status_field = $xml->xpath('//formElement[name="' . AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME . '"]//formElement[name="' . AlwaysThereFieldsExporter::JIRA_STATUS_NAME . '"]')[0];
        assertEquals(\Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $status_field['type'], 'There is a status field in the right column');
        assertEquals('Status', $status_field->label);
        assertEquals(\Tracker_FormElement_Field_List_Bind_Static::TYPE, $status_field->bind['type']);
        assertCount(3, $status_field->bind->items->item);
        assertEquals('V1', $status_field->bind->items->item[0]['ID']);
        assertEquals('A new state', $status_field->bind->items->item[0]['label']);

        $mapping = $field_mapping_collection->getMappingFromJiraField('status');
        assert($mapping instanceof ListFieldMapping);
        $values = $mapping->getBoundValues();
        assertEquals('10000', $values[0]->getId());
        assertEquals('V1', $values[0]->getXMLId());
        assertEquals('1', $values[0]->getXMLIdValue());
        assertEquals('A new state', $values[0]->getName());
    }
}
