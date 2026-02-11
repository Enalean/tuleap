<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

use FRSRelease;
use FRSReleaseFactory;
use PFUser;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSReleasePermissionManagerTest extends TestCase
{
    private FRSReleaseFactory&Stub $release_factory;

    private ReleasePermissionManager $release_permission_manager;

    private PFUser $user;

    private FRSRelease&Stub $release;

    #[\Override]
    protected function setUp(): void
    {
        $this->release_factory = $this->createStub(FRSReleaseFactory::class);

        $this->release_permission_manager = new ReleasePermissionManager(
            $this->release_factory
        );

        $this->user    = UserTestBuilder::aUser()->build();
        $this->release = $this->createStub(FRSRelease::class);
    }

    public function testReturnsTrueWhenUserCanReadTheRelease(): void
    {
        $this->release_factory->method('userCanRead')->willReturn(true);

        self::assertTrue(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release)
        );
    }

    public function testReturnsFalseWhenUserCannotReadTheRelease(): void
    {
        $this->release_factory->method('userCanRead')->willReturn(false);

        self::assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release)
        );
    }
}
