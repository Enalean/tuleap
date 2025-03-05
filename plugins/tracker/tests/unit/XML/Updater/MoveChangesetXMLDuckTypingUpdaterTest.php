<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Updater;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\FieldMapping;
use Tuleap\Tracker\Action\IsArtifactLinkFieldVerifier;
use Tuleap\Tracker\Action\IsPermissionsOnArtifactFieldVerifier;
use Tuleap\Tracker\Action\OpenListFieldVerifier;
use Tuleap\Tracker\Action\UserGroupOpenListFieldVerifier;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionDuckTypingMatcher;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\SearchUserGroupsValuesByFieldIdAndUserGroupIdStub;
use Tuleap\Tracker\Test\Stub\SearchUserGroupsValuesByIdStub;
use Tuleap\Tracker\Test\Stub\UpdateArtifactLinkXMLStub;
use Tuleap\Tracker\Tracker\XML\Updater\BindOpenValueForDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\BindValueForDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\MoveChangesetXMLDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\OpenListUserGroupsByDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\PermissionsByDuckTypingUpdater;
use XML_SimpleXMLCDATAFactory;
use XMLImportHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MoveChangesetXMLDuckTypingUpdaterTest extends TestCase
{
    private const CURRENT_USER_ID               = 104;
    private const SUBMITTER_USER_ID             = 101;
    private const ARTIFACT_SUBMISSION_TIMESTAMP = 1686468600;
    private const ARTIFACT_MOVE_TIMESTAMP       = 1686580073;

    private MoveChangesetXMLDuckTypingUpdater $updater;
    private \PFUser $current_user;
    private \PFUser $artifact_submitter;
    private \Tracker $source_tracker;
    private \Tracker $destination_tracker;

    protected function setUp(): void
    {
        $this->current_user       = UserTestBuilder::anActiveUser()->withId(self::CURRENT_USER_ID)->build();
        $this->artifact_submitter = UserTestBuilder::anActiveUser()->withId(self::SUBMITTER_USER_ID)->build();
        $XML_updater              = new MoveChangesetXMLUpdater();
        $cdata_factory            = new XML_SimpleXMLCDATAFactory();
        $bind_value_updater       = new BindValueForDuckTypingUpdater(
            new FieldValueMatcher(
                new XMLImportHelper(
                    $this->createMock(\UserManager::class)
                )
            ),
            $XML_updater,
            $cdata_factory
        );
        $bind_open_value_updater  = new BindOpenValueForDuckTypingUpdater(
            new FieldValueMatcher(
                new XMLImportHelper(
                    $this->createMock(\UserManager::class)
                )
            ),
            $XML_updater,
            $cdata_factory
        );
        $permissions_updater      = new PermissionsByDuckTypingUpdater(
            new PermissionDuckTypingMatcher(),
            $XML_updater
        );
        $this->source_tracker     = TrackerTestBuilder::aTracker()
            ->withName('Bugs')
            ->withProject(ProjectTestBuilder::aProject()->withPublicName('Guinea Pig')->build())
            ->build();

        $this->destination_tracker = TrackerTestBuilder::aTracker()
            ->withName('Bugs')
            ->withProject(ProjectTestBuilder::aProject()->withPublicName('Hamsters')->build())
            ->build();

        $user_groups_bind_values = [
            ['id' => 18, 'field_id' => 452, 'ugroup_id' => 101, 'is_hidden' => 0],
            ['id' => 19, 'field_id' => 452, 'ugroup_id' => 102, 'is_hidden' => 0],
        ];

        $this->updater = new MoveChangesetXMLDuckTypingUpdater(
            $XML_updater,
            $bind_value_updater,
            $bind_open_value_updater,
            $permissions_updater,
            new OpenListUserGroupsByDuckTypingUpdater(
                SearchUserGroupsValuesByIdStub::withValues($user_groups_bind_values),
                SearchUserGroupsValuesByFieldIdAndUserGroupIdStub::withValues($user_groups_bind_values),
                UGroupRetrieverStub::buildWithUserGroups(),
                UGroupRetrieverStub::buildWithUserGroups(),
                $XML_updater,
                $cdata_factory,
            ),
            UpdateArtifactLinkXMLStub::build(),
            new OpenListFieldVerifier(),
            new UserGroupOpenListFieldVerifier(),
            new IsPermissionsOnArtifactFieldVerifier(),
            new IsArtifactLinkFieldVerifier()
        );
    }

    public function testItRemovesNotMigrateableFieldsFromChangesetAndUpdatesOtherFields(): void
    {
        $source_status_field_id      = 3;
        $destination_status_field_id = 23;

        $source_severity_field_id      = 2;
        $destination_severity_field_id = 22;

        $source_multiple_list_field_id      = 4;
        $destination_multiple_list_field_id = 24;

        $source_assigned_to_field_id      = 5;
        $destination_assigned_to_field_id = 25;

        $source_cc_field_id      = 6;
        $destination_cc_field_id = 26;

        $source_cc_field_bind = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField($source_cc_field_id)
                ->withMultipleValues()
                ->withName('cc')
                ->build()
        )->withUserGroups([
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(100)->withName('semi-crispy')->build(),
            ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName('crispy')->build(),
        ])->build();

        $destination_cc_field_bind = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField($destination_cc_field_id)
                ->withMultipleValues()
                ->withName('cc')
                ->build()
        )->withUserGroups([
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(200)->withName('semi-crispy')->build(),
        ])->build();

        $source_status_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($source_status_field_id)->withName('status')->build()
        )->withStaticValues([
            105 => 'New',
            106 => 'In Progress',
            107 => 'Fixed',
        ])->build();

        $destination_status_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($destination_status_field_id)->withName('status')->build()
        )->withStaticValues([
            205 => 'Todo',
            206 => 'In Progress',
            207 => 'Fixed',
        ])->build();

        $source_severity_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($source_severity_field_id)->withName('severity')->build()
        )->withStaticValues([
            113 => 'Low Impact',
            114 => 'Major Impact',
            115 => 'Critical Impact',
        ])->build();

        $destination_severity_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($destination_severity_field_id)->withName('severity')->build()
        )->withStaticValues([
            213 => 'Low Impact',
            214 => 'Major Impact',
            215 => 'Critical Impact',
        ])->build();

        $source_static_multiple_list_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($source_multiple_list_field_id)
                ->withMultipleValues()
                ->withName('multiple')
                ->build()
        )->withStaticValues([
            216 => 'Value A',
            217 => 'Value B',
            218 => 'Value C',
        ])->build();

        $destination_static_multiple_list_field_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField($destination_multiple_list_field_id)
                ->withMultipleValues()
                ->withName('multiple')
                ->build()
        )->withStaticValues([
            316 => 'Value B',
            317 => 'Value C',
            318 => 'Value D',
        ])->build();

        $jolasti                       = UserTestBuilder::anActiveUser()->withId(104)->withUserName("Joe l'asticot")->build();
        $source_assigned_to_field_bind = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField($source_assigned_to_field_id)
                ->withMultipleValues()
                ->withName('assigned_to')
                ->build()
        )->withUsers([
            $jolasti,
            UserTestBuilder::anActiveUser()->withId(105)->withUserName('John Doe')->build(),
        ])->build();

        $destination_assigned_to_field_bind = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField($destination_assigned_to_field_id)
                ->withMultipleValues()
                ->withName('assigned_to')
                ->build()
        )->withUsers([
            $jolasti,
            UserTestBuilder::anActiveUser()->withId(106)->withUserName('Jeanne Doe')->build(),
        ])->build();

        $source_title_field                = StringFieldBuilder::aStringField(1)->withName('summary')->build();
        $source_severity_field             = $source_severity_field_bind->getField();
        $source_status_field               = $source_status_field_bind->getField();
        $source_static_multiple_list_field = $source_static_multiple_list_field_bind->getField();
        $source_assigned_to_field          = $source_assigned_to_field_bind->getField();
        $source_details_field              = TextFieldBuilder::aTextField(5)->withName('details')->build();
        $source_close_date_field           = DateFieldBuilder::aDateField(6)->withName('close_date')->build();
        $source_initial_effort_field       = FloatFieldBuilder::aFloatField(7)->withName('initial_effort')->build();
        $source_remaining_effort_field     = FloatFieldBuilder::aFloatField(8)->withName('remaining_effort')->build();
        $source_not_existing_field         = StringFieldBuilder::aStringField(9)->withName('notexisting')->build();
        $source_cc_field                   = $source_cc_field_bind->getField();
        $source_permissions_field          = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method('getName')->willReturn('permissions');
        $source_computed_field = $this->createStub(\Tracker_FormElement_Field_Computed::class);
        $source_computed_field->method('getName')->willReturn('computed');

        $source_open_list_static_field     = ListStaticBindBuilder::aStaticBind(
            OpenListFieldBuilder::anOpenListField()->withId(10)->withName('open_static')->build()
        )->build()->getField();
        $source_open_list_user_field       = ListUserBindBuilder::aUserBind(
            OpenListFieldBuilder::anOpenListField()->withId(11)->withName('open_users')->build()
        )->build()->getField();
        $source_open_list_user_group_field = ListUserGroupBindBuilder::aUserGroupBind(
            OpenListFieldBuilder::anOpenListField()->withId(12)->withTracker($this->source_tracker)->withName('open_ugroups')->build()
        )->build()->getField();

        $destination_open_list_static_field     = ListStaticBindBuilder::aStaticBind(
            OpenListFieldBuilder::anOpenListField()->withId(30)->withName('open_static')->build()
        )->build()->getField();
        $destination_open_list_user_field       = ListUserBindBuilder::aUserBind(
            OpenListFieldBuilder::anOpenListField()->withId(31)->withName('open_users')->build()
        )->build()->getField();
        $destination_open_list_user_group_field = ListUserGroupBindBuilder::aUserGroupBind(
            OpenListFieldBuilder::anOpenListField()->withId(32)->withTracker($this->destination_tracker)->withName('open_ugroups')->build()
        )->build()->getField();

        $source_external_field      = ExternalFieldBuilder::anExternalField(13)->withName('external_field')->build();
        $destination_external_field = ExternalFieldBuilder::anExternalField(23)->withName('external_field')->build();

        $source_artifactlink_field      = ArtifactLinkFieldBuilder::anArtifactLinkField(34)->withLabel('artifact_link')->build();
        $destination_artifactlink_field = ArtifactLinkFieldBuilder::anArtifactLinkField(35)->withLabel('artifact_link')->build();

        $source_not_movable_external_field = ExternalFieldBuilder::anExternalField(14)->withName('external_field_not_movable')->build();

        $destination_title_field                = StringFieldBuilder::aStringField(21)->withName('summary')->build();
        $destination_severity_field             = $destination_severity_field_bind->getField();
        $destination_status_field               = $destination_status_field_bind->getField();
        $destination_static_multiple_list_field = $destination_static_multiple_list_field_bind->getField();
        $destination_details_field              = TextFieldBuilder::aTextField(25)->withName('details')->build();
        $destination_close_date_field           = DateFieldBuilder::aDateField(26)->withName('close_date')->build();
        $destination_initial_effort_field       = FloatFieldBuilder::aFloatField(27)->withName('initial_effort')->build();
        $destination_remaining_effort_field     = FloatFieldBuilder::aFloatField(28)->withName('remaining_effort')->build();
        $destination_assigned_to_field          = $destination_assigned_to_field_bind->getField();
        $destination_cc_field                   = $destination_cc_field_bind->getField();
        $destination_permissions_field          = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method('getName')->willReturn('permissions');
        $destination_permissions_field->method('getAllUserGroups')->willReturn([
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('crusty')->build(),
        ]);
        $destination_computed_field = $this->createStub(\Tracker_FormElement_Field_Computed::class);
        $destination_computed_field->method('getName')->willReturn('computed');

        $fields = DuckTypedMoveFieldCollection::fromFields(
            [
                $source_title_field,
                $source_severity_field,
                $source_status_field,
                $source_details_field,
                $source_close_date_field,
                $source_initial_effort_field,
                $source_remaining_effort_field,
                $source_open_list_static_field,
                $source_open_list_user_field,
                $source_open_list_user_group_field,
                $source_computed_field,
                $source_external_field,
                $source_artifactlink_field,
            ],
            [
                $source_not_existing_field,
                $source_not_movable_external_field,
            ],
            [
                $source_static_multiple_list_field,
                $source_assigned_to_field,
                $source_cc_field,
                $destination_permissions_field,
            ],
            [
                FieldMapping::fromFields($source_title_field, $destination_title_field),
                FieldMapping::fromFields($source_severity_field, $destination_severity_field),
                FieldMapping::fromFields($source_status_field, $destination_status_field),
                FieldMapping::fromFields($source_details_field, $destination_details_field),
                FieldMapping::fromFields($source_close_date_field, $destination_close_date_field),
                FieldMapping::fromFields($source_initial_effort_field, $destination_initial_effort_field),
                FieldMapping::fromFields($source_remaining_effort_field, $destination_remaining_effort_field),
                FieldMapping::fromFields($source_static_multiple_list_field, $destination_static_multiple_list_field),
                FieldMapping::fromFields($source_assigned_to_field, $destination_assigned_to_field),
                FieldMapping::fromFields($source_cc_field, $destination_cc_field),
                FieldMapping::fromFields($source_permissions_field, $destination_permissions_field),
                FieldMapping::fromFields($source_open_list_static_field, $destination_open_list_static_field),
                FieldMapping::fromFields($source_open_list_user_field, $destination_open_list_user_field),
                FieldMapping::fromFields($source_open_list_user_group_field, $destination_open_list_user_group_field),
                FieldMapping::fromFields($source_external_field, $destination_external_field),
                FieldMapping::fromFields($source_artifactlink_field, $destination_artifactlink_field),
            ]
        );

        $artifact_xml = $this->getXMLArtifact();

        $this->updater->updateFromDuckTypingCollection(
            $this->current_user,
            $artifact_xml,
            $this->artifact_submitter,
            self::ARTIFACT_SUBMISSION_TIMESTAMP,
            self::ARTIFACT_MOVE_TIMESTAMP,
            $fields,
            $this->source_tracker,
        );

        $this->assertCount(16, $artifact_xml->changeset->field_change);
        self::assertSame($destination_title_field->getName(), (string) $artifact_xml->changeset->field_change[0]->attributes()->field_name);
        self::assertSame($destination_details_field->getName(), (string) $artifact_xml->changeset->field_change[1]->attributes()->field_name);
        self::assertSame($destination_status_field->getName(), (string) $artifact_xml->changeset->field_change[2]->attributes()->field_name);
        self::assertSame($destination_severity_field->getName(), (string) $artifact_xml->changeset->field_change[3]->attributes()->field_name);
        self::assertSame($destination_close_date_field->getName(), (string) $artifact_xml->changeset->field_change[4]->attributes()->field_name);
        self::assertSame($destination_initial_effort_field->getName(), (string) $artifact_xml->changeset->field_change[5]->attributes()->field_name);
        self::assertSame($destination_remaining_effort_field->getName(), (string) $artifact_xml->changeset->field_change[6]->attributes()->field_name);
        self::assertSame($destination_static_multiple_list_field->getName(), (string) $artifact_xml->changeset->field_change[7]->attributes()->field_name);
        self::assertSame($destination_assigned_to_field->getName(), (string) $artifact_xml->changeset->field_change[8]->attributes()->field_name);
        self::assertSame($destination_cc_field->getName(), (string) $artifact_xml->changeset->field_change[9]->attributes()->field_name);
        self::assertSame($destination_permissions_field->getName(), (string) $artifact_xml->changeset->field_change[10]->attributes()->field_name);
        self::assertSame($destination_open_list_user_group_field->getName(), (string) $artifact_xml->changeset->field_change[11]->attributes()->field_name);
        self::assertSame($destination_open_list_static_field->getName(), (string) $artifact_xml->changeset->field_change[12]->attributes()->field_name);
        self::assertSame($destination_open_list_user_field->getName(), (string) $artifact_xml->changeset->field_change[13]->attributes()->field_name);
        self::assertSame($destination_computed_field->getName(), (string) $artifact_xml->changeset->field_change[14]->attributes()->field_name);

        $this->assertCount(1, $artifact_xml->changeset->external_field_change);
        self::assertSame($destination_external_field->getName(), (string) $artifact_xml->changeset->external_field_change[0]->attributes()->field_name);
    }

    private function getXMLArtifact(): \SimpleXMLElement
    {
        return simplexml_load_string(
            file_get_contents(__DIR__ . '/_fixtures/bugs_artifact.xml'),
            \SimpleXMLElement::class,
            LIBXML_NONET
        );
    }
}
