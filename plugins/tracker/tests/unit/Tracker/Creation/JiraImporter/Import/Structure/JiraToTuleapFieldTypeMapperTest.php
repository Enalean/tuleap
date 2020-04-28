<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;

final class JiraToTuleapFieldTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \SimpleXMLElement
     */
    private $jira_atf_fieldset;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldXmlExporter
     */
    private $field_exporter;

    /**
     * @var JiraToTuleapFieldTypeMapper
     */
    private $mapper;

    /**
     * @var \SimpleXMLElement
     */
    private $jira_custom_fieldset;

    protected function setUp(): void
    {
        $this->field_exporter = Mockery::mock(FieldXmlExporter::class);
        $this->mapper         = new JiraToTuleapFieldTypeMapper(
            $this->field_exporter,
            new ErrorCollector()
        );

        $this->jira_atf_fieldset = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><formElement type="fieldset"/>'
        );

        $this->jira_custom_fieldset = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><formElement type="fieldset"/>'
        );
    }

    public function testJiraSummaryFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'summary',
            'Summary',
            true,
            'summary'
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_atf_fieldset,
                Tracker_FormElement_Field_String::TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                1,
                $jira_field->isRequired(),
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraDescriptionFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'description',
            'Description',
            false,
            'description'
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_atf_fieldset,
                Tracker_FormElement_Field_Text::TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                2,
                $jira_field->isRequired(),
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraTextFieldFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'String Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:textfield'
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_custom_fieldset,
                Tracker_FormElement_Field_String::TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                1,
                $jira_field->isRequired(),
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraTextAreaFieldIsMappedToStringField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'Text Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:textarea'
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_custom_fieldset,
                Tracker_FormElement_Field_Text::TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                2,
                $jira_field->isRequired(),
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraFloatFieldIsMappedToFloatField(): void
    {
        $jira_field = new JiraFieldAPIRepresentation(
            'fieldid',
            'String Field',
            false,
            'com.atlassian.jira.plugin.system.customfieldtypes:float'
        );

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_custom_fieldset,
                Tracker_FormElement_Field_Float::TYPE,
                $jira_field->getId(),
                $jira_field->getLabel(),
                $jira_field->getId(),
                3,
                $jira_field->isRequired(),
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }
}
