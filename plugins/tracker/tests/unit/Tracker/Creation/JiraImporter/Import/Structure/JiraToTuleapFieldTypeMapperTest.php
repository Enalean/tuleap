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

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;

final class JiraToTuleapFieldTypeMapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface $logger;
    private JiraToTuleapFieldTypeMapper $mapper;
    private XMLTracker $xml_tracker;
    private FieldMappingCollection $field_mapping_collection;
    private IDGenerator $id_generator;

    protected function setUp(): void
    {
        $this->logger = new class extends AbstractLogger {
            public $messages = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[$level][] = $message;
            }
        };
        $this->mapper = new JiraToTuleapFieldTypeMapper(
            new ErrorCollector(),
            $this->logger
        );

        $this->id_generator = new FieldAndValueIDGenerator();
        $builder            = new AlwaysThereFieldsExporter();
        $jira_client        = new class extends JiraCloudClientStub {
        };

        $this->field_mapping_collection = new FieldMappingCollection();

        $this->xml_tracker = $builder->exportFields(
            new XMLTracker($this->id_generator, 'whatever'),
            new StatusValuesCollection(
                $jira_client,
                new NullLogger()
            ),
            $this->field_mapping_collection,
        );
    }

    public static function getJiraFieldsAreMappedToXMLObjects(): iterable
    {
        yield 'JiraSummaryFieldIsMappedToStringField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'summary',
                'Summary',
                true,
                'summary',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="left_column"]//formElement[name="summary"]')[0];

                self::assertEquals('string', $node['type']);
                self::assertEquals('Summary', $node->label);
                self::assertEquals(1, (int) $node['rank']);
                self::assertEquals('1', $node['required']);

                $mapping = $collection->getMappingFromJiraField('summary');
                self::assertEquals('summary', $mapping->getFieldName());
            },
        ];

        yield 'JiraDescriptionFieldIsMappedToStringField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'description',
                'Description',
                false,
                'description',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="left_column"]//formElement[name="description"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_TEXT_TYPE, $node['type']);
                self::assertEquals('Description', $node->label);
                self::assertEquals(2, (int) $node['rank']);
                self::assertFalse(isset($node['required']), 'Field is not required');

                $mapping = $collection->getMappingFromJiraField('description');
                self::assertEquals('description', $mapping->getFieldName());
            },
        ];

        yield 'JiraTextFieldFieldIsMappedToStringField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'fieldid',
                'String Field',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:textfield',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="fieldid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_STRING_TYPE, $node['type']);
                self::assertEquals('String Field', $node->label);

                $mapping = $collection->getMappingFromJiraField('fieldid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_STRING_TYPE, $mapping->getType());
            },
        ];

        yield 'testJiraTextAreaFieldIsMappedToStringField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'fieldid',
                'Text Field',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:textarea',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="fieldid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_TEXT_TYPE, $node['type']);
                self::assertEquals('fieldid', $node->name);
                self::assertEquals('Text Field', $node->label);

                $mapping = $collection->getMappingFromJiraField('fieldid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_TEXT_TYPE, $mapping->getType());
            },
        ];

        yield 'testJiraFloatFieldIsMappedToFloatField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'fieldid',
                'Float Field',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:float',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="fieldid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_FLOAT_TYPE, $node['type']);
                self::assertEquals('fieldid', $node->name);
                self::assertEquals('Float Field', $node->label);

                $mapping = $collection->getMappingFromJiraField('fieldid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_FLOAT_TYPE, $mapping->getType());
            },
        ];

        yield 'testJiraDatepickerFieldIsMappedToDateField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'fieldid',
                'Datepicker Field',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:datepicker',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="fieldid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_DATE_TYPE, $node['type']);
                self::assertEquals('Datepicker Field', $node->label);
                self::assertFalse(isset($container_node->formElements[0]->properties), 'Time is not displayed');

                $mapping = $collection->getMappingFromJiraField('fieldid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_DATE_TYPE, $mapping->getType());
            },
        ];

        yield 'testJiraDatetimeFieldIsMappedToDateFieldWithTimeDisplayed' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'fieldid',
                'DateTimePicker Field',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:datetime',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="fieldid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_DATE_TYPE, $node['type']);
                self::assertEquals('DateTimePicker Field', $node->label);
                self::assertEquals('1', $node->properties['display_time']);

                $mapping = $collection->getMappingFromJiraField('fieldid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_DATE_TYPE, $mapping->getType());
            },
        ];

        yield 'testJiraPriorityFieldIsMappedToSelectBoxField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'priority',
                'Priorité',
                false,
                'priority',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 1, 'name' => 'P1'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 2, 'name' => 'P2'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME . '"]//formElement[name="priority"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, (string) $node['type']);
                self::assertEquals('priority', $node->name);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('P1', $node->bind->items->item[0]['label']);
                self::assertEquals('P2', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('priority');
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'testJiraRadioButtonsFieldIsMappedToRadioButtonField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'radiobuttonsid',
                'Radio buttons',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 1, 'name' => 'red'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 2, 'name' => 'green'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="radiobuttonsid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE, $node['type']);
                self::assertEquals('Radio buttons', $node->label);
                self::assertEquals('static', $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('red', $node->bind->items->item[0]['label']);
                self::assertEquals('green', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('radiobuttonsid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'testJiraMultiSelectFieldIsMappedToMultiSelectBoxField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'multiselectid',
                'Multi Select',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:multiselect',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 1, 'name' => 'V1'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 2, 'name' => 'V2'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="multiselectid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Multi Select', $node->label);
                self::assertEquals('static', $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('V1', $node->bind->items->item[0]['label']);
                self::assertEquals('V2', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('multiselectid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'testJiraSelectFieldIsMappedToSelectBoxField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'selectid',
                'Select Single',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:select',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 1, 'name' => 'foo'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 2, 'name' => 'bar'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="selectid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Select Single', $node->label);
                self::assertEquals('static', $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('foo', $node->bind->items->item[0]['label']);
                self::assertEquals('bar', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('selectid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'do not have the same ID twice in generated lists' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'selectid',
                'Select Single',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:select',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 1, 'name' => 'CIRRUS'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 2, 'name' => '*CIRRUS'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="selectid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Select Single', $node->label);
                self::assertEquals('static', $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertNotEquals($node->bind->items->item[0]['ID'], $node->bind->items->item[1]['ID']);

                $mapping = $collection->getMappingFromJiraField('selectid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'testJiraMultiUserPickerFieldIsMappedToMultiSelectBoxField' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'multiselectid',
                'Multi Select',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:multiuserpicker',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="multiselectid"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Multi Select', $node->label);
                self::assertEquals('users', $node->bind['type']);
                self::assertCount(1, $node->bind->items->item);
                self::assertEquals('group_members', $node->bind->items->item[0]['label']);

                $mapping = $collection->getMappingFromJiraField('multiselectid');
                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Users::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira assignee field is mapped to select box bound to users' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME,
                'Assignee',
                false,
                AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME,
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME . '"]//formElement[name="' . AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME . '"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Assignee', $node->label);
                self::assertEquals('users', $node->bind['type']);
                self::assertCount(1, $node->bind->items->item);
                self::assertEquals('group_members', $node->bind->items->item[0]['label']);

                $mapping = $collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME);
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Users::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira reporter field is mapped to select box bound to users' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                AlwaysThereFieldsExporter::JIRA_REPORTER_NAME,
                'Reporter',
                false,
                AlwaysThereFieldsExporter::JIRA_REPORTER_NAME,
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME . '"]//formElement[name="' . AlwaysThereFieldsExporter::JIRA_REPORTER_NAME . '"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, (string) $node['type']);
                self::assertEquals('Reporter', $node->label);
                self::assertEquals('users', $node->bind['type']);
                self::assertCount(1, $node->bind->items->item);
                self::assertEquals('group_members', $node->bind->items->item[0]['label']);

                $mapping = $collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_REPORTER_NAME);
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Users::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira generic user picker field is mapped to select box bound to users' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'custom_123214',
                'Reviewers',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:userpicker',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="custom_123214"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Reviewers', $node->label);
                self::assertEquals('users', $node->bind['type']);
                self::assertCount(1, $node->bind->items->item);
                self::assertEquals('group_members', $node->bind->items->item[0]['label']);

                $mapping = $collection->getMappingFromJiraField('custom_123214');
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Users::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira version is mapped to a multi select box field' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'versions',
                'Identified in version',
                false,
                'versions',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10109, 'name' => '1.0'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10110, 'name' => '2.0'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="versions"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Identified in version', $node->label);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('1.0', $node->bind->items->item[0]['label']);
                self::assertEquals('2.0', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('versions');
                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira Components is mapped to a multi select box field' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'components',
                'Components',
                false,
                'components',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10109, 'name' => 'Comp 01'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10110, 'name' => 'Comp 02'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="components"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Components', $node->label);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('Comp 01', $node->bind->items->item[0]['label']);
                self::assertEquals('Comp 02', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('components');
                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira custom version is mapped to a select box field' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'customfield_10002',
                'Version',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:version',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10109, 'name' => '1.0'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10110, 'name' => '2.0'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="customfield_10002"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Version', $node->label);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('1.0', $node->bind->items->item[0]['label']);
                self::assertEquals('2.0', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('customfield_10002');
                self::assertEquals(Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira custom multiversion is mapped to a multi select box field' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'customfield_10001',
                'Version(s)',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:multiversion',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10109, 'name' => '1.0'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10110, 'name' => '2.0'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="customfield_10001"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $node['type']);
                self::assertEquals('Version(s)', $node->label);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('1.0', $node->bind->items->item[0]['label']);
                self::assertEquals('2.0', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('customfield_10001');
                self::assertEquals(Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'Jira custom multicheckboxes is mapped to a checkbox field' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'customfield_10004',
                'CheckBox',
                false,
                'com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes',
                [
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10109, 'name' => '1.0'], new FieldAndValueIDGenerator()),
                    JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses(['id' => 10110, 'name' => '2.0'], new FieldAndValueIDGenerator()),
                ],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="' . AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME . '"]//formElement[name="customfield_10004"]')[0];

                self::assertEquals(Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE, $node['type']);
                self::assertEquals('CheckBox', $node->label);
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $node->bind['type']);
                self::assertCount(2, $node->bind->items->item);
                self::assertEquals('1.0', $node->bind->items->item[0]['label']);
                self::assertEquals('2.0', $node->bind->items->item[1]['label']);

                $mapping = $collection->getMappingFromJiraField('customfield_10004');
                self::assertEquals(Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE, $mapping->getType());
                self::assertEquals(Tracker_FormElement_Field_List_Bind_Static::TYPE, $mapping->getBindType());
            },
        ];

        yield 'field is available at submit and update' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'summary',
                'Summary',
                true,
                'summary',
                [],
                true,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="left_column"]//formElement[name="summary"]')[0];

                $permissions = $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '"]');
                self::assertCount(3, $permissions);

                self::assertEquals('UGROUP_ANONYMOUS', $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_READ"]')[0]['ugroup']);
                self::assertEquals('UGROUP_REGISTERED', $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_SUBMIT"]')[0]['ugroup']);
                self::assertEquals('UGROUP_PROJECT_MEMBERS', $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_UPDATE"]')[0]['ugroup']);
            },
        ];

        yield 'field is only available at update' => [
            'jira_field' => new JiraFieldAPIRepresentation(
                'summary',
                'Summary',
                true,
                'summary',
                [],
                false,
            ),
            'tests' => function (SimpleXMLElement $exported_tracker, FieldMappingCollection $collection) {
                $node = $exported_tracker->xpath('//formElement[name="left_column"]//formElement[name="summary"]')[0];

                $permissions = $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '"]');
                self::assertCount(2, $permissions);

                self::assertEquals('UGROUP_ANONYMOUS', $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_READ"]')[0]['ugroup']);
                self::assertEquals('UGROUP_PROJECT_MEMBERS', $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_UPDATE"]')[0]['ugroup']);
                self::assertCount(0, $exported_tracker->xpath('//permissions/permission[@REF="' . $node['ID'] . '" and @type="PLUGIN_TRACKER_FIELD_SUBMIT"]'));
            },
        ];
    }

    /**
     * @dataProvider getJiraFieldsAreMappedToXMLObjects
     */
    public function testJiraFieldsAreMappedToXMLObjects(JiraFieldAPIRepresentation $jira_field, callable $tests): void
    {
        $xml_tracker = $this->mapper->exportFieldToXml(
            $jira_field,
            $this->xml_tracker,
            $this->id_generator,
            new PlatformConfiguration(),
            $this->field_mapping_collection,
        );

        $xml              = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $exported_tracker = $xml_tracker->export($xml);

        $tests($exported_tracker, $this->field_mapping_collection);
    }

    public function testUnsupportedFieldIsLoggedForDebugPurpose(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'votes_id',
            'Votes',
            false,
            'votes',
            [],
            true,
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->xml_tracker,
            $this->id_generator,
            new PlatformConfiguration(),
            $this->field_mapping_collection,
        );

        assertEquals(" |_ Field votes_id (votes) ignored ", $this->logger->messages['debug'][0]);
    }

    public function testStoryPointsFieldIsNotAddedTwiceWhenConfiguredOnTheCreationScreen(): void
    {
        $story_points_jira_field_id = 'customfield_10014';

        $platform_configuration = new PlatformConfiguration();
        $platform_configuration->setStoryPointsField($story_points_jira_field_id);

        $jira_field = new JiraFieldAPIRepresentation(
            $story_points_jira_field_id,
            'Story points',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:float',
            [],
            true,
        );

        $xml_tracker = $this->mapper->exportFieldToXml(
            $jira_field,
            $this->xml_tracker,
            $this->id_generator,
            $platform_configuration,
            $this->field_mapping_collection,
        );

        $xml              = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $exported_tracker = $xml_tracker->export($xml);

        assertEmpty($exported_tracker->xpath('//formElement[label="Story points"]'));
    }
}
