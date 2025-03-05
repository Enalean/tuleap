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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset;

use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmissionDateProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SUBMISSION_TIMESTAMP = 1631178060;

    public function testItBuildsFromChangeset(): void
    {
        $artifact  = ArtifactTestBuilder::anArtifact(709)->build();
        $changeset = new \Tracker_Artifact_Changeset(4042, $artifact, 120, (string) self::SUBMISSION_TIMESTAMP, null);

        $date = SubmissionDateProxy::fromChangeset($changeset);
        self::assertSame(self::SUBMISSION_TIMESTAMP, $date->getValue());
    }
}
