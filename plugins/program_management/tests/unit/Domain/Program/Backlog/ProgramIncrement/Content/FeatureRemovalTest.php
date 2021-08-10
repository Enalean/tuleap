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
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;

final class FeatureRemovalTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenFeatureIsLinkedToAnAlreadyPlannedUserStory(): void
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $program         = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user_identifier);
        $feature         = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 741, $user_identifier, $program);

        $this->expectException(FeatureHasPlannedUserStoryException::class);
        FeatureRemoval::fromFeature(
            VerifyLinkedUserStoryIsNotPlannedStub::buildLinkedStories(),
            $feature,
            UserCanPrioritize::fromUser(VerifyPrioritizeFeaturesPermissionStub::canPrioritize(), UserPermissionsStub::aRegularUser(), $user_identifier, $program)
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $program         = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user_identifier);
        $feature         = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 76, $user_identifier, $program);

        $payload = FeatureRemoval::fromFeature(
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            $feature,
            UserCanPrioritize::fromUser(VerifyPrioritizeFeaturesPermissionStub::canPrioritize(), UserPermissionsStub::aRegularUser(), $user_identifier, $program)
        );
        self::assertSame(76, $payload->feature_id);
    }
}
