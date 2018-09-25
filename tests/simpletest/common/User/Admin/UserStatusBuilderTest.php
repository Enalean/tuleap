<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class UserStatusBuilderTest extends \TuleapTestCase
{
    /**
     * @var UserStatusChecker
     */
    private $user_status_checker;

    /**
     * @var array
     */
    private $status_with_restricted;

    /**
     * @var array
     */
    private $status;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var UserStatusBuilder
     */
    private $user_status_builder;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();

        $this->user                = mock('PFUser');
        $this->user_status_checker = new UserStatusChecker();
        $this->user_status_builder = new UserStatusBuilder($this->user_status_checker);

        $this->status = array(
            array(
                'key'        => PFUser::STATUS_ACTIVE,
                'status'     => null,
                'is_current' => false
            ),
            array(
                'key'        => PFUser::STATUS_SUSPENDED,
                'status'     => null,
                'is_current' => false
            ),
            array(
                'key'        => PFUser::STATUS_DELETED,
                'status'     => null,
                'is_current' => false
            )
        );

        $this->status_with_restricted = array(
            array(
                'key'        => PFUser::STATUS_ACTIVE,
                'status'     => null,
                'is_current' => false
            ),
            array(
                'key'        => PFUser::STATUS_RESTRICTED,
                'status'     => null,
                'is_current' => false
            ),
            array(
                'key'        => PFUser::STATUS_SUSPENDED,
                'status'     => null,
                'is_current' => false
            ),
            array(
                'key'        => PFUser::STATUS_DELETED,
                'status'     => null,
                'is_current' => false
            )
        );
    }

    public function itRetrievesRestrictedStatusWhenPlatformAllowsRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->user)->isRestricted()->returns(false);

        $this->assertEqual($this->user_status_builder->getStatus($this->user), $this->status_with_restricted);
    }

    public function itRetrievesRestrictedStatusWhenAUserHasRestrictedStatus()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->user)->isRestricted()->returns(true);

        $this->assertEqual($this->user_status_builder->getStatus($this->user), $this->status_with_restricted);
    }

    public function itShouldNeverReturnsRestrictedWhenNoUserIsRestrictedAndPlatformDoesNotAllowRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->user)->isRestricted()->returns(false);

        $this->assertEqual($this->user_status_builder->getStatus($this->user), $this->status);
    }
}
