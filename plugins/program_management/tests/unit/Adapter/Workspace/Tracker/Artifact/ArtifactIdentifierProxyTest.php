<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact;

use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactIdentifierProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 883;

    public function testItBuildsFromArtifact(): void
    {
        $full_artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
        $proxy         = ArtifactIdentifierProxy::fromArtifact($full_artifact);
        self::assertSame(self::ARTIFACT_ID, $proxy->getId());
    }
}
