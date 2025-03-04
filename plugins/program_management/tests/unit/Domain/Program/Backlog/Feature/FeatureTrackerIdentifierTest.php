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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 974;

    private function getFeatureTracker(): FeatureTrackerIdentifier
    {
        $feature = FeatureIdentifierBuilder::withId(518);

        return FeatureTrackerIdentifier::fromFeature(
            RetrieveTrackerOfFeatureStub::withId(self::TRACKER_ID),
            $feature
        );
    }

    public function testItBuildsFromFeature(): void
    {
        $tracker = $this->getFeatureTracker();
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }
}
