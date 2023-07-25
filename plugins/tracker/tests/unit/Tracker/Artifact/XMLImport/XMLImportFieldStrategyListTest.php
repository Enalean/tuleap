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

namespace Tuleap\Tracker\Artifact\XMLImport;

use DataAccessResult;
use Mockery;
use PFUser;
use SimpleXMLElement;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyList;
use Tracker_FormElement_Field_List;
use TrackerXmlFieldsMapping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use User\XML\Import\IFindUserFromXMLReference;

/**
 * @covers Tracker_Artifact_XMLImport_XMLImportFieldStrategyList
 */
class XMLImportFieldStrategyListTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BindStaticValueDao
     */
    private $static_value_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IFindUserFromXMLReference
     */
    private $user_finder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerXmlFieldsMapping
     */
    private $xml_fields_mapping;
    /**
     * @var Tracker_Artifact_XMLImport_XMLImportFieldStrategyList
     */
    private $import_field_strategy;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $submitter;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $artifact;

    public function setUp(): void
    {
        $this->submitter          = Mockery::mock(PFUser::class);
        $this->artifact           = Mockery::mock(Artifact::class);
        $this->static_value_dao   = Mockery::mock(BindStaticValueDao::class);
        $this->user_finder        = Mockery::mock(IFindUserFromXMLReference::class);
        $this->xml_fields_mapping = Mockery::mock(TrackerXmlFieldsMapping::class);

        $this->import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            $this->xml_fields_mapping
        );
    }

    public function testGetFieldDataStaticValueGetNewValueID(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id"><![CDATA[13727]]></value>
            </field_change>'
        );

        $xml_fields_mapping = new class implements TrackerXmlFieldsMapping {
            public function getNewValueId($old_value_id)
            {
                if ($old_value_id === '13727') {
                    return '111';
                }
                throw new \LogicException('test not covered');
            }

            public function getNewOpenValueId($old_value_id)
            {
            }
        };

        $import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            $xml_fields_mapping
        );

        $result = $import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueGetNewValueIDWhenXMLHasSpaces(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id">
                <![CDATA[13727]]>
              </value>
            </field_change>'
        );

        $xml_fields_mapping = new class implements TrackerXmlFieldsMapping {
            public function getNewValueId($old_value_id)
            {
                if ($old_value_id === '13727') {
                    return '111';
                }
                throw new \LogicException('test not covered');
            }

            public function getNewOpenValueId($old_value_id)
            {
            }
        };

        $import_field_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
            $this->static_value_dao,
            $this->user_finder,
            $xml_fields_mapping
        );

        $result = $import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueGetNewValueIDThatIsString(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="id"><![CDATA[bug_status_todo]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->shouldReceive("getNewValueId")->with('bug_status_todo')->andReturn('111');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataStaticValueSearchValueByLabel(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive("getId")->andReturn(12);

        $data_access_result = Mockery::mock(DataAccessResult::class);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="other"><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("searchValueByLabel")->withArgs([12, "13727"])->andReturn(
            $data_access_result
        );

        $data_access_result->shouldReceive("getRow")->andReturn(["id" => "42"]);

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([42], $result);
    }

    public function testGetFieldDataStaticValueSearchValueByLabelWithoutResultReturnNull(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive("getId")->andReturn(12);

        $data_access_result = Mockery::mock(DataAccessResult::class);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value format="other"><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("searchValueByLabel")->withArgs([12, "13727"])->andReturn(
            $data_access_result
        );

        $data_access_result->shouldReceive("getRow")->andReturn(false);

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataStaticValueWithoutFormatSearchValueByLabelReturnNull(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive("getId")->andReturn(12);

        $data_access_result = Mockery::mock(DataAccessResult::class);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
              <value><![CDATA[13727]]></value>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("searchValueByLabel")->withArgs([12, "13727"])->andReturn(
            $data_access_result
        );

        $data_access_result->shouldReceive("getRow")->andReturn(false);

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataStaticWithEmptyValueReturnNull(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive("getId")->andReturn(12);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="static">
                  <value/>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("searchValueByLabel")->never();

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListValueGetNewValueID(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
              <value format="id"><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->shouldReceive("getNewValueId")->with(104)->andReturn('111');

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals([111], $result);
    }

    public function testGetFieldDataUgroupListValueWithWrongFormatReturnNull(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
              <value format="other"><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->shouldReceive("getNewValueId")->never();

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListValueWithoutFormatReturnNull(): void
    {
        $field        = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
                 <value><![CDATA[104_2]]></value>
            </field_change>'
        );
        $this->xml_fields_mapping->shouldReceive("getNewValueId")->never();

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertNull($result[0]);
        self::assertCount(1, $result);
    }

    public function testGetFieldDataUgroupListWithEmptyValueReturnNull(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $field->shouldReceive("getId")->andReturn(12);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="ugroups">
                  <value/>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("getNewValueId")->never();

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
    }

    public function testGetFieldDataUserListValueReturnUserId(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(104);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="users">
              <value format="ldap"><![CDATA[104]]></value>
            </field_change>'
        );

        $this->user_finder->shouldReceive("getUser")->andReturn($user);

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));
        self::assertEquals(104, $result[0]);
    }

    public function testGetFieldDataUserListWithEmptyValueReturnNull(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <field_change field_name="status" type="list" bind="users">
                  <value/>
            </field_change>'
        );
        $this->static_value_dao->shouldReceive("getUser")->never();

        $result = $this->import_field_strategy->getFieldData($field, $field_change, $this->submitter, $this->artifact, PostCreationContext::withNoConfig(false));

        self::assertNull($result[0]);
    }
}
