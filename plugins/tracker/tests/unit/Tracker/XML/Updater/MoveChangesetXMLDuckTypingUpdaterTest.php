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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\FieldMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;
use XMLImportHelper;

final class MoveChangesetXMLDuckTypingUpdaterTest extends TestCase
{
    private const CURRENT_USER_ID               = 104;
    private const SUBMITTER_USER_ID             = 101;
    private const ARTIFACT_SUBMISSION_TIMESTAMP = 1686468600;
    private const ARTIFACT_MOVE_TIMESTAMP       = 1686580073;

    private MoveChangesetXMLDuckTypingUpdater $updater;
    private \PFUser $current_user;
    private \PFUser $artifact_submitter;

    protected function setUp(): void
    {
        $this->current_user       = UserTestBuilder::anActiveUser()->withId(self::CURRENT_USER_ID)->build();
        $this->artifact_submitter = UserTestBuilder::anActiveUser()->withId(self::SUBMITTER_USER_ID)->build();
        $bind_value_updater       = new BindValueForDuckTypingUpdater(
            new FieldValueMatcher(
                new XMLImportHelper(
                    $this->createMock(\UserManager::class)
                )
            )
        );
        $this->tracker            = TrackerTestBuilder::aTracker()
            ->withName("Bugs")
            ->withProject(ProjectTestBuilder::aProject()->withPublicName("Guinea Pig")->build())
            ->build();

        $this->updater = new MoveChangesetXMLDuckTypingUpdater(
            new MoveChangesetXMLUpdater(),
            $bind_value_updater
        );
    }

    public function testItParsesXmlChangesetAndRemoveNotMigrateableFields(): void
    {
        $source_status_field_id      = 3;
        $destination_status_field_id = 23;

        $source_severity_field_id      = 2;
        $destination_severity_field_id = 22;

        $source_status_field_bind = TrackerFormElementListStaticBindBuilder::aBind()->withFieldId($source_status_field_id)->withFieldName("status")->withStaticValues([
            105 => "New",
            106 => "In Progress",
            107 => "Fixed",
        ])->build();

        $destination_status_field_bind = TrackerFormElementListStaticBindBuilder::aBind()->withFieldId($destination_status_field_id)->withFieldName("status")->withStaticValues([
            205 => "Todo",
            206 => "In Progress",
            207 => "Fixed",
        ])->build();

        $source_severity_field_bind = TrackerFormElementListStaticBindBuilder::aBind()->withFieldId($source_severity_field_id)->withFieldName("severity")->withStaticValues([
            113 => "Low Impact",
            114 => "Major Impact",
            115 => "Critical Impact",
        ])->build();

        $destination_severity_field_bind = TrackerFormElementListStaticBindBuilder::aBind()->withFieldId($destination_severity_field_id)->withFieldName("severity")->withStaticValues([
            213 => "Low Impact",
            214 => "Major Impact",
            215 => "Critical Impact",
        ])->build();

        $source_title_field            = TrackerFormElementStringFieldBuilder::aStringField(1)->withName("summary")->build();
        $source_severity_field         = $source_severity_field_bind->getField();
        $source_status_field           = $source_status_field_bind->getField();
        $source_assigned_to_field      = TrackerFormElementListFieldBuilder::aListField(4)->withName("assigned_to")->build();
        $source_details_field          = TrackerFormElementTextFieldBuilder::aTextField(5)->withName("details")->build();
        $source_close_date_field       = TrackerFormElementDateFieldBuilder::aDateField(6)->withName("close_date")->build();
        $source_initial_effort_field   = TrackerFormElementFloatFieldBuilder::aFloatField(7)->withName("initial_effort")->build();
        $source_remaining_effort_field = TrackerFormElementFloatFieldBuilder::aFloatField(8)->withName("remaining_effort")->build();

        $destination_title_field            = TrackerFormElementStringFieldBuilder::aStringField(21)->withName("summary")->build();
        $destination_severity_field         = $destination_severity_field_bind->getField();
        $destination_status_field           = $destination_status_field_bind->getField();
        $destination_details_field          = TrackerFormElementTextFieldBuilder::aTextField(25)->withName("details")->build();
        $destination_close_date_field       = TrackerFormElementDateFieldBuilder::aDateField(26)->withName("close_date")->build();
        $destination_initial_effort_field   = TrackerFormElementFloatFieldBuilder::aFloatField(27)->withName("initial_effort")->build();
        $destination_remaining_effort_field = TrackerFormElementFloatFieldBuilder::aFloatField(28)->withName("remaining_effort")->build();

        $fields = DuckTypedMoveFieldCollection::fromFields(
            [
                $source_title_field,
                $source_severity_field,
                $source_status_field,
                $source_details_field,
                $source_close_date_field,
                $source_initial_effort_field,
                $source_remaining_effort_field,
            ],
            [
                $source_assigned_to_field,
            ],
            [],
            [
                FieldMapping::fromFields($source_title_field, $destination_title_field),
                FieldMapping::fromFields($source_severity_field, $destination_severity_field),
                FieldMapping::fromFields($source_status_field, $destination_status_field),
                FieldMapping::fromFields($source_details_field, $destination_details_field),
                FieldMapping::fromFields($source_close_date_field, $destination_close_date_field),
                FieldMapping::fromFields($source_initial_effort_field, $destination_initial_effort_field),
                FieldMapping::fromFields($source_remaining_effort_field, $destination_remaining_effort_field),
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
            $this->tracker,
        );

        $this->assertCount(7, $artifact_xml->changeset->field_change);
        $this->assertSame($destination_title_field->getName(), (string) $artifact_xml->changeset->field_change[0]->attributes()->field_name);
        $this->assertSame($destination_details_field->getName(), (string) $artifact_xml->changeset->field_change[1]->attributes()->field_name);
        $this->assertSame($destination_status_field->getName(), (string) $artifact_xml->changeset->field_change[2]->attributes()->field_name);
        $this->assertSame($destination_severity_field->getName(), (string) $artifact_xml->changeset->field_change[3]->attributes()->field_name);
        $this->assertSame($destination_close_date_field->getName(), (string) $artifact_xml->changeset->field_change[4]->attributes()->field_name);
        $this->assertSame($destination_initial_effort_field->getName(), (string) $artifact_xml->changeset->field_change[5]->attributes()->field_name);
        $this->assertSame($destination_remaining_effort_field->getName(), (string) $artifact_xml->changeset->field_change[6]->attributes()->field_name);
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
