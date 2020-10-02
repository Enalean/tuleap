<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

class InvitationSenderGateKeeperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var InvitationLimitChecker
     */
    private $limit_checker;

    /**
     * @var InvitationSenderGateKeeper
     */
    private $gate_keeper;
    /**
     * @var InviteBuddyConfiguration
     */
    private $configuration;
    private $current_user;

    protected function setUp(): void
    {
        $this->current_user = Mockery::spy(PFUser::class);

        $this->configuration = Mockery::mock(InviteBuddyConfiguration::class);

        $this->limit_checker = Mockery::mock(InvitationLimitChecker::class);

        $this->gate_keeper = new InvitationSenderGateKeeper(
            new \Valid_Email(),
            $this->configuration,
            $this->limit_checker
        );
    }

    public function testItRaisesAnExceptionIfConfigurationDisablesTheFeature(): void
    {
        $this->configuration->shouldReceive('canBuddiesBeInvited')->once()->andReturn(false);

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->gate_keeper->checkNotificationsCanBeSent($this->current_user, ["john@example.com", "doe@example.com"]);
    }

    public function testItRaisesAnExceptionIfNoEmailIsGiven(): void
    {
        $this->configuration->shouldReceive('canBuddiesBeInvited')->once()->andReturn(true);

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->gate_keeper->checkNotificationsCanBeSent($this->current_user, []);
    }

    public function testItRaisesAnExceptionIfOneOfTheEmailIsNotValid(): void
    {
        $this->configuration->shouldReceive('canBuddiesBeInvited')->once()->andReturn(true);

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->gate_keeper->checkNotificationsCanBeSent($this->current_user, ["john@example.com", "whatever", "doe@example.com"]);
    }

    public function testItRaisesAnExceptionIfUserReachedLimit(): void
    {
        $this->configuration->shouldReceive('canBuddiesBeInvited')->once()->andReturn(true);
        $this->limit_checker->shouldReceive('checkForNewInvitations')->once()->andThrow(
            InvitationSenderGateKeeperException::class
        );

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->gate_keeper->checkNotificationsCanBeSent($this->current_user, ["john@example.com", "doe@example.com"]);
    }

    public function testItDoesNotRaiseAnExceptionIfEverythingIsOk(): void
    {
        $this->configuration->shouldReceive('canBuddiesBeInvited')->once()->andReturn(true);
        $this->limit_checker->shouldReceive('checkForNewInvitations')->once();

        $this->gate_keeper->checkNotificationsCanBeSent($this->current_user, ["john@example.com", "doe@example.com"]);
    }
}
