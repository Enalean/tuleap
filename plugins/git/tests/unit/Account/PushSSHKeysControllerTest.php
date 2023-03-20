<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Account;

use CSRFSynchronizerToken;
use Feedback;
use Git_RemoteServer_GerritServerFactory;
use Git_UserAccountManager;
use Git_UserSynchronisationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder as UserTestBuilderAlias;

final class PushSSHKeysControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Git_UserAccountManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $user_account_manager;
    /**
     * @var Git_RemoteServer_GerritServerFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $gerrit_server_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var PushSSHKeysController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->user_account_manager = \Mockery::spy(\Git_UserAccountManager::class);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_server_factory->shouldReceive('getRemoteServersForUser')->andReturns([Mockery::mock('Git_RemoteServer_GerritServer')]);

        $this->logger = \Mockery::spy(LoggerInterface::class);

        $this->user = UserTestBuilderAlias::aUser()->withId(120)->build();

        $this->controller = new PushSSHKeysController(
            Mockery::mock(CSRFSynchronizerToken::class, ['check' => true]),
            $this->user_account_manager,
            $this->gerrit_server_factory,
            $this->logger,
        );
    }

    public function testItDoesNotPushKeysIfUserIsInvalid()
    {
        $this->expectException(\Tuleap\Request\ForbiddenException::class);

        $this->user_account_manager->shouldReceive('pushSSHKeys')->never();

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItLogsAnErrorIfSSHKeyPushFails(): void
    {
        $this->user_account_manager->shouldReceive('pushSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $this->logger->shouldReceive('error')->once();

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItAddsResponseFeedbackIfSSHKeyPushFails(): void
    {
        $this->user_account_manager->shouldReceive('pushSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $this->gerrit_server_factory->shouldReceive('getRemoteServersForUser')->andReturns([]);

        $layout_inspector = new LayoutInspector();

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->build(),
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::ERROR, $feedback[0]['level']);
    }
}
