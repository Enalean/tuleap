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

use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class CanPermissionsBeFullyMovedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Artifact $artifact;
    private CanPermissionsBeFullyMovedVerifier $verifier;

    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->verifier = new CanPermissionsBeFullyMovedVerifier();
    }

    public function testItReturnsFalseWhenTheLastChangesetValueIsNull(): void
    {
        self::assertFalse(
            $this->verifier->canAllPermissionsBeFullyMoved(
                $this->getSourceFieldWithLastChangesetValue(null),
                $this->getDestinationFieldWithUserGroupsNames([
                    ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName("semi-crusty")->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName("crusty")->build(),
                ]),
                $this->artifact
            )
        );
    }

    public function testItReturnsFalseWhenThereIsAtLeastOneUserGroupMissingInDestinationField(): void
    {
        self::assertFalse(
            $this->verifier->canAllPermissionsBeFullyMoved(
                $this->getSourceFieldWithLastChangesetValue([\ProjectUGroup::PROJECT_MEMBERS, "semi-crusty"]),
                $this->getDestinationFieldWithUserGroupsNames([
                    ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName("semi-crusty")->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName("crusty")->build(),
                ]),
                $this->artifact
            )
        );
    }

    public function testItReturnsTrueWhenAllSelectedUserGroupsExistInDestinationField(): void
    {
        self::assertFalse(
            $this->verifier->canAllPermissionsBeFullyMoved(
                $this->getSourceFieldWithLastChangesetValue([\ProjectUGroup::PROJECT_MEMBERS, "semi-crusty"]),
                $this->getDestinationFieldWithUserGroupsNames([
                    ProjectUGroupTestBuilder::buildProjectMembers(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName("semi-crusty")->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName("crusty")->build(),
                ]),
                $this->artifact
            )
        );
    }

    /**
     * @param string[] | null $user_groups_names
     */
    private function getSourceFieldWithLastChangesetValue(?array $user_groups_names): Tracker_FormElement_Field_PermissionsOnArtifact
    {
        $source_field = $this->createStub(Tracker_FormElement_Field_PermissionsOnArtifact::class);

        if ($user_groups_names === null) {
            $source_field->method('getLastChangesetValue')->willReturn(null);

            return $source_field;
        }

        $last_changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $last_changeset_value->method('getUgroupNamesFromPerms')->willReturn($user_groups_names);

        $source_field->method('getLastChangesetValue')->willReturn($last_changeset_value);

        return $source_field;
    }

    /**
     * @param \ProjectUGroup[] $user_groups
     */
    private function getDestinationFieldWithUserGroupsNames(array $user_groups): Tracker_FormElement_Field_PermissionsOnArtifact
    {
        $destination_field = $this->createStub(Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_field->method('getAllUserGroups')->willReturn($user_groups);

        return $destination_field;
    }
}
