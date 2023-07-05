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

namespace Tuleap\Document\Tests\Action;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Action\CanUserFieldValuesBeFullyMovedVerifier;
use Tuleap\Tracker\Artifact\Artifact;

final class CanUserFieldValuesBeFullyMovedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Stub & \Tracker_FormElement_Field_List $source_field;
    private Stub & \Tracker_FormElement_Field_List $destination_field;
    private Stub & Artifact $artifact;
    private Stub & Tracker_Artifact_ChangesetValue_List $changeset_value;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user              = UserTestBuilder::anActiveUser()->withId(101)->withUserName('Mildred Favorito')->build();
        $this->source_field      = $this->createStub(Tracker_FormElement_Field_List::class);
        $this->destination_field = $this->createStub(Tracker_FormElement_Field_List::class);

        $this->changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_List::class);
        $this->artifact        = $this->createStub(Artifact::class);
    }

    public function testUserCanNotBeMoveWhenFieldItsNotAUserBoundListField(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            1,
            $this->createStub(\Tracker_Artifact_Changeset::class),
            $this->source_field,
            1,
            "",
            ""
        );
        $this->source_field->method('getLastChangesetValue')->willReturn($changeset_value);
        $retrieve_user = RetrieveUserByIdStub::withUser(UserTestBuilder::anActiveUser()->build());
        $verifier      = new CanUserFieldValuesBeFullyMovedVerifier($retrieve_user);

        $this->assertFalse($verifier->canAllUserFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact));
    }

    public function testUserCanNotBeMovedWhenUserIsNotFound(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $retrieve_user = RetrieveUserByIdStub::withNoUser();
        $verifier      = new CanUserFieldValuesBeFullyMovedVerifier($retrieve_user);
        $this->changeset_value->method('getListValues')->willReturn([
            new \Tracker_FormElement_Field_List_Bind_UsersValue(1, 'Irrelevant', 'Mildred Favorito'),
        ]);

        $this->assertFalse($verifier->canAllUserFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact));
    }

    public function testUserCanNotBeMovedWhenUserIsAnonymous(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $retrieve_user = RetrieveUserByIdStub::withUser(UserTestBuilder::anAnonymousUser()->build());
        $verifier      = new CanUserFieldValuesBeFullyMovedVerifier($retrieve_user);
        $this->changeset_value->method('getListValues')->willReturn([
            new \Tracker_FormElement_Field_List_Bind_UsersValue(1, 'Irrelevant', 'Mildred Favorito'),
        ]);

        $this->assertFalse($verifier->canAllUserFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact));
    }

    public function testUserCanNotBeMovedWhenUserDoesNotExistsInTarget(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $retrieve_user = RetrieveUserByIdStub::withUser(UserTestBuilder::anActiveUser()->withId(234)->build());
        $verifier      = new CanUserFieldValuesBeFullyMovedVerifier($retrieve_user);
        $this->changeset_value->method('getListValues')->willReturn([
            new \Tracker_FormElement_Field_List_Bind_UsersValue(1, 'Irrelevant', 'Mildred Favorito'),
        ]);
        $this->destination_field->method('checkValueExists')->willReturn(false);

        $this->assertFalse($verifier->canAllUserFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact));
    }

    public function testUserCanBeMoved(): void
    {
        $this->source_field->method('getLastChangesetValue')->willReturn($this->changeset_value);
        $retrieve_user = RetrieveUserByIdStub::withUser($this->user);
        $verifier      = new CanUserFieldValuesBeFullyMovedVerifier($retrieve_user);
        $this->changeset_value->method('getListValues')->willReturn([
            new \Tracker_FormElement_Field_List_Bind_UsersValue(101, 'Irrelevant', 'Mildred Favorito'),
        ]);
        $this->destination_field->method('checkValueExists')->willReturn(true);

        $this->assertTrue($verifier->canAllUserFieldValuesBeMoved($this->source_field, $this->destination_field, $this->artifact));
    }
}
