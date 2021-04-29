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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Program;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureRemovalTest extends TestCase
{
    public function testItThrowsWhenFeatureIsLinkedToAnAlreadyPlannedUserStory(): void
    {
        $user    = UserTestBuilder::aUser()->withId(104)->build();
        $program = new Program(110);
        $feature = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 741, $user, $program);

        $this->expectException(FeatureHasPlannedUserStoryException::class);
        FeatureRemoval::fromFeature(
            new VerifyLinkedUserStoryIsNotPlannedStub(true),
            $feature,
            $user
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = new Program(110);
        $feature = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 76, $user, $program);

        $payload = FeatureRemoval::fromFeature(
            new VerifyLinkedUserStoryIsNotPlannedStub(),
            $feature,
            $user
        );
        self::assertSame(76, $payload->feature_id);
    }
}
