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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureRemovalTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID = 741;
    private FeatureIdentifier $feature;
    private UserCanPrioritize $user_can_prioritize;

    protected function setUp(): void
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $program         = ProgramIdentifierBuilder::buildWithId(110);
        $feature         = FeatureIdentifierBuilder::build(self::FEATURE_ID, 110);

        $this->feature             = $feature;
        $this->user_can_prioritize = UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $user_identifier,
            $program,
            null
        );
    }

    public function testItThrowsWhenFeatureIsLinkedToAnAlreadyPlannedUserStory(): void
    {
        $this->expectException(FeatureHasPlannedUserStoryException::class);
        FeatureRemoval::fromFeature(
            VerifyHasAtLeastOnePlannedUserStoryStub::withPlannedUserStory(),
            $this->feature,
            $this->user_can_prioritize
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $payload = FeatureRemoval::fromFeature(
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
            $this->feature,
            $this->user_can_prioritize
        );
        self::assertSame(self::FEATURE_ID, $payload->feature_id);
    }
}
