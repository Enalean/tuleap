<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management\VerifierChain;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\ForgePermissionsRetrieverStub;
use Tuleap\Timetracking\Tests\Stub\VerifyInChainManagerIsAllowedToSeeTimesStub;
use Tuleap\Timetracking\Widget\Management\TimeSpentInArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ManagerHasRestReadOnlyAdminPermissionVerifierTest extends VerifierChainTestBase
{
    public function testTrueWhenManagerIsRestReadOnlyAdministrator(): void
    {
        $alice   = UserTestBuilder::buildWithDefaults();
        $manager = UserTestBuilder::aRandomActiveUser()->build();

        $time = new TimeSpentInArtifact(
            $alice,
            ArtifactTestBuilder::anArtifact(101)->build(),
            123,
        );

        $next_in_chain = VerifyInChainManagerIsAllowedToSeeTimesStub::withoutAllowed();

        $verifier = new ManagerHasRestReadOnlyAdminPermissionVerifier(
            ForgePermissionsRetrieverStub::withPermission(new RestReadOnlyAdminPermission()),
        );
        $verifier->chain($next_in_chain);

        self::assertTrue($verifier->isManagerAllowedToSeeTimes($time, $manager));
        self::assertFalse($next_in_chain->hasBeenCalled());
    }

    public function testDelegatesToNextInChainIfNoPermissionDelegation(): void
    {
        $alice   = UserTestBuilder::buildWithDefaults();
        $manager = UserTestBuilder::aRandomActiveUser()->build();

        $time = new TimeSpentInArtifact(
            $alice,
            ArtifactTestBuilder::anArtifact(101)->build(),
            123,
        );

        $this->assertNextInChainDelegation(
            static fn () => new ManagerHasRestReadOnlyAdminPermissionVerifier(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            $manager,
            $time,
        );
    }
}
