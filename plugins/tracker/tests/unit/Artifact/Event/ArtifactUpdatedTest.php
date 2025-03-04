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

namespace Tuleap\Tracker\Artifact\Event;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactUpdatedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromArtifact(): void
    {
        $user          = UserTestBuilder::aUser()->withId(101)->build();
        $artifact      = ArtifactTestBuilder::anArtifact(150)->build();
        $changeset     = new \Tracker_Artifact_Changeset('1974', $artifact, $user->getId(), 1234567890, null);
        $old_changeset = new \Tracker_Artifact_Changeset('1973', $artifact, $user->getId(), 1234567800, null);

        $event = new ArtifactUpdated($artifact, $user, $changeset, $old_changeset);
        self::assertSame($user, $event->getUser());
        self::assertSame($artifact, $event->getArtifact());
        self::assertSame($changeset, $event->getChangeset());
        self::assertSame($old_changeset, $event->getOldChangeset());
    }
}
