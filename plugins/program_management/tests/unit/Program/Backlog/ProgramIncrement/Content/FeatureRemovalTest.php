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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureRemovalTest extends TestCase
{
    public function testItReturnsNullWhenFeatureIsNotVisibleByUser(): void
    {
        $feature = new FeatureIdentifier(76);
        $user    = UserTestBuilder::aUser()->build();
        $program = new Program(110);
        self::assertNull(
            FeatureRemoval::fromRawData(
                $feature,
                $user,
                $program,
                new VerifyIsVisibleFeatureStub(false),
                new VerifyLinkedUserStoryIsNotPlannedStub(),
            )
        );
    }

    public function testItThrowsWhenFeatureIsLinkedToAnAlreadyPlannedUserStory(): void
    {
        $feature = new FeatureIdentifier(76);
        $user    = UserTestBuilder::aUser()->withId(104)->build();
        $program = new Program(110);

        $this->expectException(FeatureHasPlannedUserStoryException::class);
        FeatureRemoval::fromRawData(
            $feature,
            $user,
            $program,
            new VerifyIsVisibleFeatureStub(),
            new VerifyLinkedUserStoryIsNotPlannedStub(true),
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $feature = new FeatureIdentifier(76);
        $user    = UserTestBuilder::aUser()->build();
        $program = new Program(110);

        $payload = FeatureRemoval::fromRawData(
            $feature,
            $user,
            $program,
            new VerifyIsVisibleFeatureStub(),
            new VerifyLinkedUserStoryIsNotPlannedStub(),
        );
        self::assertSame(76, $payload->feature_id);
    }
}
