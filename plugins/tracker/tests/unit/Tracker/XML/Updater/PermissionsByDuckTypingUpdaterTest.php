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

declare(strict_types=1);

namespace Tuleap\Tracker\XML\Updater;

use Monolog\Test\TestCase;
use ProjectUGroup;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Tracker\Test\Stub\MatchPermissionsByDuckTypingStub;
use Tuleap\Tracker\Tracker\XML\Updater\PermissionsByDuckTypingUpdater;

final class PermissionsByDuckTypingUpdaterTest extends TestCase
{
    public function testItRemovesAllUserGroupsNotAppearingInDestinationField(): void
    {
        $updater = new PermissionsByDuckTypingUpdater(
            MatchPermissionsByDuckTypingStub::withUserGroupsInDestinationField([
                ProjectUGroup::PROJECT_ADMIN,
                "semi-crusty",
            ]),
            new MoveChangesetXMLUpdater()
        );

        $changeset_xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset/>');
        $field_change           = $changeset_xml->addChild('field_change');
        $field_change->value[0] = ProjectUGroup::PROJECT_MEMBERS;
        $field_change->value[1] = "semi-crusty";
        $field_change->value[2] = "crusty";

        $updater->updatePermissionsForDuckTypingMove(
            $changeset_xml,
            $this->createStub(Tracker_FormElement_Field_PermissionsOnArtifact::class),
            0
        );

        self::assertCount(1, $changeset_xml->field_change[0]->value);
        self::assertSame("semi-crusty", (string) $changeset_xml->field_change[0]->value);
    }
}
