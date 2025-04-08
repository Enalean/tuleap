<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XMLImport;

use PFUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use TestHelper;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyList;
use TrackerXmlFieldsMapping;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Stub\TrackerXmlFieldsMappingStub;
use User\XML\Import\IFindUserFromXMLReference;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[CoversClass(Tracker_Artifact_XMLImport_XMLImportFieldStrategyList::class)]
final class XMLImportFieldStrategyListTest extends TestCase
{
    private BindStaticValueDao&MockObject $static_value_dao;
    private IFindUserFromXMLReference&MockObject $user_finder;
    private TrackerXmlFieldsMapping&MockObject $xml_fields_mapping;
    private Tracker_Artifact_XMLImport_XMLImportFieldStrategyList $import_field_strategy;
    private PFUser $submitter;
    private Artifact $artifact;

    public function setUp(): void
    {
        $this->submitter          = UserTestBuilder::buildWithDefaults();
        $this->artifact           = ArtifactTestBuilder::anArtifact(58)->build();
        $this->static_value_dao   = $this->createMock(BindStaticValueDao::class);
        $this->user_finder        = $this->createMock(IFindUserFromXMLReference::class);
        $this->xml_fields_mapping = $this->createMock(TrackerXmlFieldsMapping::class);

        $this->import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            $this->xml_fields_mapping
        );
    }

    public function testGetFieldDataStaticValueGetNewValueID(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id"><![CDATA[13727]]></value>
            </field_change>'
        );

        $import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            TrackerXmlFieldsMappingStub::buildWithMapping(['13727' => '111']),
        );

        $result = $import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueGetNewValueIDWhenXMLHasSpaces(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id">
                <![CDATA[13727]]>
              </value>
            </field_change>'
        );

        $import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            TrackerXmlFieldsMappingStub::buildWithMapping(['13727' => '111']),
        );

        $result = $import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueGetNewValueIDThatIsString(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id"><![CDATA[bug_status_todo]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->method('getNewValueId')->with('bug_status_todo')->willReturn('111');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueSearchValueByLabel(): void
    {
        $field = ListFieldBuilder::aListField(12)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="other"><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->method('searchValueByLabel')->with(12, '13727')->willReturn(TestHelper::arrayToDar(['id' => '42']));

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([42], $result);
    }

    public function testGetFieldDataStaticValueSearchValueByLabelWithoutResultReturnNull(): void
    {
        $field = ListFieldBuilder::aListField(12)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="other"><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->method('searchValueByLabel')->with(12, '13727')->willReturn(TestHelper::emptyDar());

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataStaticValueWithoutFormatSearchValueByLabelReturnNull(): void
    {
        $field = ListFieldBuilder::aListField(12)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->method('searchValueByLabel')->with(12, '13727')->willReturn(TestHelper::emptyDar());

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataStaticWithEmptyValueReturnNull(): void
    {
        $field = ListFieldBuilder::aListField(12)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
                  <value/>
            </field_change>'
        );
        $this->static_value_dao->expects(self::never())->method('searchValueByLabel');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListValueGetNewValueID(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
              <value format="id"><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->method('getNewValueId')->with('104_2')->willReturn('111');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataUgroupListValueWithWrongFormatReturnNull(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
              <value format="other"><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->expects(self::never())->method('getNewValueId');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListValueWithoutFormatReturnNull(): void
    {
        $field        = ListFieldBuilder::aListField(485)->build();
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
                 <value><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->expects(self::never())->method('getNewValueId');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListWithEmptyValueReturnNull(): void
    {
        $field = ListFieldBuilder::aListField(12)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
                  <value/>
            </field_change>'
        );

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
    }

    public function testGetFieldDataUserListValueReturnUserId(): void
    {
        $user = UserTestBuilder::buildWithId(104);

        $field = ListFieldBuilder::aListField(485)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="users">
              <value format="ldap"><![CDATA[104]]></value>
            </field_change>'
        );

        $this->user_finder->method('getUser')->willReturn($user);

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals(104, $result[0]);
    }

    public function testGetFieldDataUserListWithEmptyValueReturnNull(): void
    {
        $field = ListFieldBuilder::aListField(485)->build();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="users">
                  <value/>
            </field_change>'
        );

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
    }
}
