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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactRestUpdateConditionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
    }

    public function testUpdateThrowsWhenUserCannotUpdate(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        (new ArtifactRestUpdateConditionsChecker())->checkIfArtifactUpdateCanBePerformedThroughREST(
            UserTestBuilder::anAnonymousUser()->build(),
            ArtifactTestBuilder::anArtifact(1)->build(),
        );
    }

    public function testUpdateThrowsWhenThereWasAConcurrentModification(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->expects(self::once())->method('userCanUpdate')->willReturn(true);
        $artifact->expects(self::once())->method('getLastUpdateDate')->willReturn(1500000000);

        $_SERVER['HTTP_IF_UNMODIFIED_SINCE'] = '1234567890';

        $this->expectException(RestException::class);
        $this->expectExceptionCode(412);

        (new ArtifactRestUpdateConditionsChecker())->checkIfArtifactUpdateCanBePerformedThroughREST(
            UserTestBuilder::anAnonymousUser()->build(),
            $artifact,
        );
    }
}
