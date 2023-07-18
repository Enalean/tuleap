<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tracker_FormElement_Field_OpenList;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\Test\Builders\TrackerFormElementOpenListBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\SearchUserGroupsValuesByFieldIdAndUserGroupIdStub;
use Tuleap\Tracker\Test\Stub\SearchUserGroupsValuesByIdStub;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;
use XML_SimpleXMLCDATAFactory;

final class OpenListUserGroupsByDuckTypingUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tracker_FormElement_Field_List_Bind_Ugroups $source_bind;
    private \Tracker_FormElement_Field_List_Bind_Ugroups $target_bind;
    private \Tracker_FormElement_Field_OpenList $source_field;
    private \Tracker_FormElement_Field_OpenList $target_field;
    private XML_SimpleXMLCDATAFactory $cdata_factory;
    private MoveChangesetXMLUpdater $move_changeset_updater;

    protected function setUp(): void
    {
        $project             = ProjectTestBuilder::aProject()->build();
        $source_tracker      = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $destination_tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->source_bind   = TrackerFormElementOpenListBuilder::aBind()->withTracker($source_tracker)->buildUserGroupBind();
        $this->target_bind   = TrackerFormElementOpenListBuilder::aBind()->withTracker($destination_tracker)->buildUserGroupBind();

        assert($this->source_bind->getField() instanceof Tracker_FormElement_Field_OpenList);
        assert($this->target_bind->getField() instanceof Tracker_FormElement_Field_OpenList);
        $this->source_field =  $this->source_bind->getField();
        $this->target_field =  $this->target_bind->getField();

        $this->cdata_factory          = new XML_SimpleXMLCDATAFactory();
        $this->move_changeset_updater = new MoveChangesetXMLUpdater();
    }

    public function testItDoesNotSetWhenValueIsNotFoundInSource(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                                <artifact>
                                    <field_change field_name="open_ugroups" type="open_list" bind="ugroups">
                                      <value format="id">b18</value>
                                    </field_change>
                                </artifact>';
        $changeset_xml = new SimpleXMLElement($xml);

        $ugroup_retriever                                        = UGroupRetrieverStub::buildWithUserGroups();
        $ugroup_name_retriever                                   = UGroupRetrieverStub::buildWithUserGroups();
        $search_user_groups_values_by_id                         = SearchUserGroupsValuesByIdStub::withoutValues();
        $search_user_groups_values_by_field_id_and_user_group_id = SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withoutValues();

        $updater = new OpenListUserGroupsByDuckTypingUpdater(
            $search_user_groups_values_by_id,
            $search_user_groups_values_by_field_id_and_user_group_id,
            $ugroup_retriever,
            $ugroup_name_retriever,
            $this->move_changeset_updater,
            $this->cdata_factory
        );

        $updater->updateUserGroupsForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->assertSame("", (string) $changeset_xml->field_change[0]->value);
    }

    public function testItDoesNotSetWhenUserGroupMappedToValueIsNotFoundInSource(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                                <artifact>
                                <field_change field_name="open_ugroups" type="open_list" bind="ugroups">
                                  <value format="id"><![CDATA[b18]]></value>
                                </field_change>
                                </artifact>';
        $changeset_xml = new SimpleXMLElement($xml);

        $ugroup_retriever                                        = UGroupRetrieverStub::buildWithUserGroups();
        $ugroup_name_retriever                                   = UGroupRetrieverStub::buildWithUserGroups();
        $search_user_groups_values_by_id                         = SearchUserGroupsValuesByIdStub::withValues([["id" => 18, "ugroup_id" => 101]]);
        $search_user_groups_values_by_field_id_and_user_group_id = SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withoutValues();

        $updater = new OpenListUserGroupsByDuckTypingUpdater(
            $search_user_groups_values_by_id,
            $search_user_groups_values_by_field_id_and_user_group_id,
            $ugroup_retriever,
            $ugroup_name_retriever,
            $this->move_changeset_updater,
            $this->cdata_factory
        );

        $updater->updateUserGroupsForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->assertSame("", (string) $changeset_xml->field_change[0]->value);
    }

    public function testItDoesNotSetWhenUserGroupIsNotFoundInDestination(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                                <artifact>
                                <field_change field_name="open_ugroups" type="open_list" bind="ugroups">
                                  <value format="id"><![CDATA[b18]]></value>
                                </field_change>
                                </artifact>';
        $changeset_xml = new SimpleXMLElement($xml);

        $ugroup_retriever                                        = UGroupRetrieverStub::buildWithUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(101)->build());
        $ugroup_name_retriever                                   = UGroupRetrieverStub::buildWithUserGroups();
        $search_user_groups_values_by_id                         = SearchUserGroupsValuesByIdStub::withValues([["id" => 18, "ugroup_id" => 101]]);
        $search_user_groups_values_by_field_id_and_user_group_id = SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withoutValues();

        $updater = new OpenListUserGroupsByDuckTypingUpdater(
            $search_user_groups_values_by_id,
            $search_user_groups_values_by_field_id_and_user_group_id,
            $ugroup_retriever,
            $ugroup_name_retriever,
            $this->move_changeset_updater,
            $this->cdata_factory
        );

        $updater->updateUserGroupsForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->assertSame("", (string) $changeset_xml->field_change[0]->value);
    }

    public function testItDoesNotSetWhenValueMappedToUserGroupIsNotFoundInDestination(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                                <artifact>
                                <field_change field_name="open_ugroups" type="open_list" bind="ugroups">
                                  <value format="id"><![CDATA[b18]]></value>
                                </field_change>
                                </artifact>';
        $changeset_xml = new SimpleXMLElement($xml);

        $ugroup_retriever                                        = UGroupRetrieverStub::buildWithUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(101)->build());
        $ugroup_name_retriever                                   = UGroupRetrieverStub::buildWithUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(101)->build());
        $search_user_groups_values_by_id                         = SearchUserGroupsValuesByIdStub::withValues([["id" => 18, "ugroup_id" => 101]]);
        $search_user_groups_values_by_field_id_and_user_group_id = SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withoutValues();

        $updater = new OpenListUserGroupsByDuckTypingUpdater(
            $search_user_groups_values_by_id,
            $search_user_groups_values_by_field_id_and_user_group_id,
            $ugroup_retriever,
            $ugroup_name_retriever,
            $this->move_changeset_updater,
            $this->cdata_factory
        );

        $updater->updateUserGroupsForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->assertSame("", (string) $changeset_xml->field_change[0]->value);
    }

    public function testItReplaceSourceValueByDestinationValue(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                                <artifact>
                                <field_change field_name="open_ugroups" type="open_list" bind="ugroups">
                                  <value format="id"><![CDATA[b18]]></value>
                                </field_change>
                                </artifact>';
        $changeset_xml = new SimpleXMLElement($xml);

        $ugroup_retriever                                        = UGroupRetrieverStub::buildWithUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(101)->build());
        $ugroup_name_retriever                                   = UGroupRetrieverStub::buildWithUserGroups(ProjectUGroupTestBuilder::aCustomUserGroup(109)->build());
        $search_user_groups_values_by_id                         = SearchUserGroupsValuesByIdStub::withValues([["id" => 18, "ugroup_id" => 101]]);
        $search_user_groups_values_by_field_id_and_user_group_id = SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withValues([["ugroup_id" => 109, "id" => 22, "field_id" => $this->target_field->getId()]]);

        $updater = new OpenListUserGroupsByDuckTypingUpdater(
            $search_user_groups_values_by_id,
            $search_user_groups_values_by_field_id_and_user_group_id,
            $ugroup_retriever,
            $ugroup_name_retriever,
            $this->move_changeset_updater,
            $this->cdata_factory
        );

        $updater->updateUserGroupsForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->assertSame("b22", (string) $changeset_xml->field_change[0]->value);
    }
}
