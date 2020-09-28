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

namespace Tuleap\InviteBuddy\Admin;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

class InviteBuddyAdminUpdateControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var InviteBuddyAdminUpdateController
     */
    private $controller;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|InviteBuddyConfiguration
     */
    private $configuration;
    /**
     * @var \CSRFSynchronizerToken|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var \ConfigDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $config_dao;

    protected function setUp(): void
    {
        $this->configuration = Mockery::mock(InviteBuddyConfiguration::class);
        $this->csrf_token    = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->config_dao    = Mockery::mock(\ConfigDao::class);

        $this->controller = new InviteBuddyAdminUpdateController(
            $this->csrf_token,
            $this->configuration,
            $this->config_dao,
        );
    }

    public function testItThrowsExceptionIfUserIsNotSuperUser(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => false])->getMock();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesNothingIfThereIsNoChange(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => true])->getMock();

        $this->configuration
            ->shouldReceive('getNbMaxInvitationsByDay')
            ->andReturn(42);

        $this->csrf_token
            ->shouldReceive('check')
            ->once();

        $this->config_dao
            ->shouldReceive('save')
            ->never();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '42')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesNothingIfValueIsNegative(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => true])->getMock();

        $this->configuration
            ->shouldReceive('getNbMaxInvitationsByDay')
            ->andReturn(42);

        $this->csrf_token
            ->shouldReceive('check')
            ->once();

        $this->config_dao
            ->shouldReceive('save')
            ->never();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '-10')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesNothingIfValueIsZero(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => true])->getMock();

        $this->configuration
            ->shouldReceive('getNbMaxInvitationsByDay')
            ->andReturn(42);

        $this->csrf_token
            ->shouldReceive('check')
            ->once();

        $this->config_dao
            ->shouldReceive('save')
            ->never();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '0')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesTheNewValue(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => true])->getMock();

        $this->configuration
            ->shouldReceive('getNbMaxInvitationsByDay')
            ->andReturn(42);

        $this->csrf_token
            ->shouldReceive('check')
            ->once();

        $this->config_dao
            ->shouldReceive('save')
            ->with('max_invitations_by_day', 10)
            ->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '10')->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
