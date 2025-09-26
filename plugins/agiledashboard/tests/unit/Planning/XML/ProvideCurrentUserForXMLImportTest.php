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

namespace Tuleap\AgileDashboard\Planning\XML;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\ProvideCurrentUser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProvideCurrentUserForXMLImportTest extends TestCase
{
    public function testCurrentUserIsWrappedAsAUserForXMLImportWhenActiveAndNotAnonymous(): void
    {
        $current_user_provider = new ProvideCurrentUserForXMLImport(
            self::buildCurrentUserProvider(UserTestBuilder::anActiveUser()->build())
        );

        $current_user = $current_user_provider->getCurrentUser();
        self::assertTrue($current_user->isSuperUser());
    }

    public function testCurrentUserIsGivenAsIsWhenAnonymous(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isActive')->willReturn(true);
        $user->method('isAnonymous')->willReturn(true);
        $user->method('isSuperUser')->willReturn(false);
        $current_user_provider = new ProvideCurrentUserForXMLImport(
            self::buildCurrentUserProvider($user)
        );

        $current_user = $current_user_provider->getCurrentUser();
        self::assertFalse($current_user->isSuperUser());
    }

    public function testCurrentUserIsGivenAsIsWhenNotActive(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isActive')->willReturn(false);
        $user->method('isSuperUser')->willReturn(false);
        $current_user_provider = new ProvideCurrentUserForXMLImport(
            self::buildCurrentUserProvider($user)
        );

        $current_user = $current_user_provider->getCurrentUser();
        self::assertFalse($current_user->isSuperUser());
    }

    private static function buildCurrentUserProvider(\PFUser $user): ProvideCurrentUser
    {
        return new class ($user) implements ProvideCurrentUser
        {
            private \PFUser $user;

            public function __construct(\PFUser $user)
            {
                $this->user = $user;
            }

            #[\Override]
            public function getCurrentUser(): \PFUser
            {
                return $this->user;
            }
        };
    }
}
