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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

final class PermissionDuckTypingMatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFalseWhenUserGroupNameDoesNotExistInDestinationField(): void
    {
        self::assertFalse(
            (new PermissionDuckTypingMatcher())->doesUserGroupExistsInDestinationField(
                $this->getDestinationField(),
                'guinea_pigs'
            )
        );
    }

    public function testItReturnsTrueWhenUserGroupNameExistsInDestinationField(): void
    {
        self::assertTrue(
            (new PermissionDuckTypingMatcher())->doesUserGroupExistsInDestinationField(
                $this->getDestinationField(),
                "semi-crusty"
            )
        );
    }

    private function getDestinationField(): Tracker_FormElement_Field_PermissionsOnArtifact
    {
        $field = $this->createStub(Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $field->method('getAllUserGroups')->willReturn([
            ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName("semi-crusty")->build(),
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName("crusty")->build(),
        ]);
        return $field;
    }
}
