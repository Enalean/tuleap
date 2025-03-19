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

namespace Tuleap\Tracker\Action;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CanUserGroupValuesBeFullyMovedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Stub&\Tracker_FormElement_Field_List $source_field;
    private Stub&\Tracker_FormElement_Field_List $destination_field;
    private Artifact $artifact;
    private Stub&Tracker_Artifact_ChangesetValue_List $changeset_value;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user         = UserTestBuilder::anActiveUser()->withId(101)->withUserName('Mildred Favorito')->build();
        $this->source_field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->source_field->method('getId')->willReturn('123');
        $this->source_field->method('getName')->willReturn('UserGroup');
        $this->destination_field = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->destination_field->method('getId')->willReturn('456');
        $this->destination_field->method('getName')->willReturn('UserGroup');

        $this->changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $this->artifact        = ArtifactTestBuilder::anArtifact(1234)->build();
    }

    public function testUserGroupCanNotBeMoveWhenFieldItsNotAUserBoundListField(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            1,
            $this->createStub(\Tracker_Artifact_Changeset::class),
            $this->source_field,
            1,
            '',
            ''
        );
        $this->source_field->method('getLastChangesetValue')->willReturn($changeset_value);
        $verifier = new CanUserGroupValuesBeFullyMovedVerifier();

        $this->assertFalse($verifier->canAllUserGroupFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact, new NullLogger()));
    }

    public function testUserGroupCanNotBeMovedWhenUserDoesNotExistsInTarget(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $verifier = new CanUserGroupValuesBeFullyMovedVerifier();
        $this->changeset_value->method('getListValues')->willReturn([
            ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(12345)->withName('perceval le gaulois')->build())->withId(1)->build(),
        ]);
        $this->destination_field->method('getAllValues')->willReturn(
            [
                ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(987)->withName('perceval le gallois')->build())->withId(1)->build(),
            ]
        );

        $this->assertFalse($verifier->canAllUserGroupFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact, new NullLogger()));
    }

    public function testUserGroupCanBeMoved(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $verifier = new CanUserGroupValuesBeFullyMovedVerifier();
        $this->changeset_value->method('getListValues')->willReturn([
            ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(12345)->withName('perceval le gaulois')->build())->withId(1)->build(),
        ]);
        $this->destination_field->method('getAllValues')->willReturn(
            [
                ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(12345)->withName('perceval le gaulois')->build())->withId(1)->build(),
            ]
        );

        $this->assertTrue($verifier->canAllUserGroupFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact, new NullLogger()));
    }
}
