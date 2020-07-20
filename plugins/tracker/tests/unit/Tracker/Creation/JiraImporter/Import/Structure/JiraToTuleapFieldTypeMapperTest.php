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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;
use XML_SimpleXMLCDATAFactory;

final class JiraToTuleapFieldTypeMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldXmlExporter
     */
    private $field_exporter;

    /**
     * @var JiraToTuleapFieldTypeMapper
     */
    private $mapper;

    /**
     * @var ContainersXMLCollection
     */
    private $containers_collection;

    /**
     * @var SimpleXMLElement
     */
    private $left_column;

    /**
     * @var SimpleXMLElement
     */
    private $custom_fieldset;

    /**
     * @var SimpleXMLElement
     */
    private $right_column;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->field_exporter = Mockery::mock(FieldXmlExporter::class);
        $this->mapper         = new JiraToTuleapFieldTypeMapper(
            $this->field_exporter,
            new ErrorCollector(),
            $this->logger
        );

        $form_elements = new SimpleXMLElement("<formElements/>");
        $this->containers_collection = (new ContainersXMLCollectionBuilder(new XML_SimpleXMLCDATAFactory()))->buildCollectionOfJiraContainersXML(
            $form_elements
        );

        $this->left_column     = $this->containers_collection->getContainerByName(ContainersXMLCollectionBuilder::LEFT_COLUMN_NAME);
        $this->right_column    = $this->containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME);
        $this->custom_fieldset = $this->containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME);
    }

    public function testJiraSummaryFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'summary',
            'Summary',
            true,
            'summary',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->left_column,
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                1,
                $jira_field->isRequired(),
                [],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraDescriptionFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'description',
            'Description',
            false,
            'description',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->left_column,
                Tracker_FormElementFactory::FIELD_TEXT_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                2,
                $jira_field->isRequired(),
                [],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraTextFieldFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'String Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:textfield',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                1,
                $jira_field->isRequired(),
                [],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraTextAreaFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'Text Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:textarea',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_TEXT_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                2,
                $jira_field->isRequired(),
                [],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraFloatFieldIsMappedToFloatField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'String Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:float',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                3,
                $jira_field->isRequired(),
                [],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraDatepickerFieldIsMappedToDateField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'Datepicker Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:datepicker',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_DATE_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                4,
                $jira_field->isRequired(),
                [
                    'display_time' => '0'
                ],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraDatetimeFieldIsMappedToDateFieldWithTimeDisplayed(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'Datepicker Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:datetime',
            []
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_DATE_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                4,
                $jira_field->isRequired(),
                [
                    'display_time' => '1'
                ],
                [],
                $collection,
                null
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraPriorityFieldIsMappedToSelectBoxField(): void
    {
        $bound_values = [
            new JiraFieldAPIAllowedValueRepresentation(
                1,
                'value01'
            ),
            new JiraFieldAPIAllowedValueRepresentation(
                2,
                'value02'
            )
        ];

        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'Priorité',
            false,
            'priority',
            $bound_values
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->right_column,
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                AlwaysThereFieldsExporter::JIRA_PRIORITY_RANK,
                $jira_field->isRequired(),
                [],
                $bound_values,
                $collection,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraRadioButtonsFieldIsMappedToRadioButtonField(): void
    {
        $bound_values = [
            new JiraFieldAPIAllowedValueRepresentation(
                1,
                'value01'
            ),
            new JiraFieldAPIAllowedValueRepresentation(
                2,
                'value02'
            )
        ];

        $jira_field = new JiraFieldAPIRepresentation(
            'radiobuttonsid',
            'Radio buttons',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons',
            $bound_values
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                5,
                $jira_field->isRequired(),
                [],
                $bound_values,
                $collection,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraMultiSelectFieldIsMappedToMultiSelectBoxField(): void
    {
        $bound_values = [
            new JiraFieldAPIAllowedValueRepresentation(
                1,
                'value01'
            ),
            new JiraFieldAPIAllowedValueRepresentation(
                2,
                'value02'
            )
        ];

        $jira_field = new JiraFieldAPIRepresentation(
            'multiselectid',
            'Multi Select',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:multiselect',
            $bound_values
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                6,
                $jira_field->isRequired(),
                [],
                $bound_values,
                $collection,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraSelectFieldIsMappedToSelectBoxField(): void
    {
        $bound_values = [
            new JiraFieldAPIAllowedValueRepresentation(
                1,
                'value01'
            ),
            new JiraFieldAPIAllowedValueRepresentation(
                2,
                'value02'
            )
        ];

        $jira_field = new JiraFieldAPIRepresentation(
            'selectid',
            'Select Single',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:select',
            $bound_values
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                5,
                $jira_field->isRequired(),
                [],
                $bound_values,
                $collection,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testJiraMultiUserPickerFieldIsMappedToMultiSelectBoxField(): void
    {
        $bound_values = [
            new JiraFieldAPIAllowedValueRepresentation(
                1,
                'value01'
            ),
            new JiraFieldAPIAllowedValueRepresentation(
                2,
                'value02'
            )
        ];

        $jira_field = new JiraFieldAPIRepresentation(
            'multiselectid',
            'Multi Select',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:multiuserpicker',
            $bound_values
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->custom_fieldset,
                Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                12,
                $jira_field->isRequired(),
                [],
                $bound_values,
                $collection,
                \Tracker_FormElement_Field_List_Bind_Users::TYPE
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }

    public function testUnsupportedFieldIsLoggedForDebugPurpose(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'votes_id',
            'Votes',
            false,
            'votes',
            []
        );

        $collection = new FieldMappingCollection();

        $this->logger->shouldReceive('debug')->with(" |_ Field votes_id (votes) ignored ")->once();

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->containers_collection,
            $collection
        );
    }
}
