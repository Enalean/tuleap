<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use ForgeAccess;
use ForgeConfig;
use PFUser;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserStatusBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use \Tuleap\ForgeConfigSandbox;

    private UserStatusChecker $user_status_checker;
    private array $status_with_restricted;
    private array $active_status_with_restricted;
    private array $status;
    private UserStatusBuilder $user_status_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_status_checker = new UserStatusChecker();
        $this->user_status_builder = new UserStatusBuilder($this->user_status_checker);

        $this->status = [
            [
                'key'        => PFUser::STATUS_ACTIVE,
                'status'     => null,
                'is_current' => true,
            ],
            [
                'key'        => PFUser::STATUS_SUSPENDED,
                'status'     => null,
                'is_current' => false,
            ],
            [
                'key'        => PFUser::STATUS_DELETED,
                'status'     => null,
                'is_current' => false,
            ],
        ];

        $this->active_status_with_restricted = [
            [
                'key'        => PFUser::STATUS_ACTIVE,
                'status'     => null,
                'is_current' => true,
            ],
            [
                'key'        => PFUser::STATUS_RESTRICTED,
                'status'     => null,
                'is_current' => false,
            ],
            [
                'key'        => PFUser::STATUS_SUSPENDED,
                'status'     => null,
                'is_current' => false,
            ],
            [
                'key'        => PFUser::STATUS_DELETED,
                'status'     => null,
                'is_current' => false,
            ],
        ];

        $this->status_with_restricted = [
            [
                'key'        => PFUser::STATUS_ACTIVE,
                'status'     => null,
                'is_current' => false,
            ],
            [
                'key'        => PFUser::STATUS_RESTRICTED,
                'status'     => null,
                'is_current' => true,
            ],
            [
                'key'        => PFUser::STATUS_SUSPENDED,
                'status'     => null,
                'is_current' => false,
            ],
            [
                'key'        => PFUser::STATUS_DELETED,
                'status'     => null,
                'is_current' => false,
            ],
        ];
    }

    public function testItRetrievesRestrictedStatusWhenPlatformAllowsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($this->active_status_with_restricted, $this->user_status_builder->getStatus($user));
    }

    public function testItRetrievesRestrictedStatusWhenAUserHasRestrictedStatus(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $user = UserTestBuilder::aRestrictedUser()->build();

        self::assertEquals($this->user_status_builder->getStatus($user), $this->status_with_restricted);
    }

    public function testItShouldNeverReturnsRestrictedWhenNoUserIsRestrictedAndPlatformDoesNotAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($this->user_status_builder->getStatus($user), $this->status);
    }
}
