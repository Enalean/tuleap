<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactWasClosedCacheTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItStoresClosedArtifacts(): void
    {
        $cache = new ArtifactWasClosedCache();

        $first_artifact  = ArtifactTestBuilder::anArtifact(6)->build();
        $second_artifact = ArtifactTestBuilder::anArtifact(35)->build();

        $cache->addClosedArtifact($first_artifact);
        $cache->addClosedArtifact($second_artifact);

        self::assertTrue($cache->isClosed($first_artifact));
        self::assertTrue($cache->isClosed($second_artifact));
    }

    public function testItReturnsFalseWhenEmpty(): void
    {
        $cache = new ArtifactWasClosedCache();

        self::assertFalse($cache->isClosed(ArtifactTestBuilder::anArtifact(12)->build()));
    }

    public function testItReturnsFalseWhenArtifactHasNotBeenStoredPreviously(): void
    {
        $cache = new ArtifactWasClosedCache();
        $cache->addClosedArtifact(ArtifactTestBuilder::anArtifact(44)->build());

        self::assertFalse($cache->isClosed(ArtifactTestBuilder::anArtifact(79)->build()));
    }
}
