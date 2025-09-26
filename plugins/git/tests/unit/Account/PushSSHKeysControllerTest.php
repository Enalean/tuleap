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

use ColinODell\PsrTestLogger\TestLogger;
use Feedback;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_UserAccountManager;
use Git_UserSynchronisationException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder as UserTestBuilderAlias;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PushSSHKeysControllerTest extends TestCase
{
    private readonly MockObject&Git_UserAccountManager $user_account_manager;
    private readonly MockObject&Git_RemoteServer_GerritServerFactory $gerrit_server_factory;
    private readonly TestLogger $logger;
    private readonly PFUser $user;
    private readonly PushSSHKeysController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_account_manager = $this->createMock(Git_UserAccountManager::class);

        $this->gerrit_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_server_factory->method('getRemoteServersForUser')->willReturn([$this->createMock(Git_RemoteServer_GerritServer::class)]);

        $this->logger = new TestLogger();

        $this->user = UserTestBuilderAlias::aUser()->withId(120)->build();

        $this->controller = new PushSSHKeysController(
            CSRFSynchronizerTokenStub::buildSelf(),
            $this->user_account_manager,
            $this->gerrit_server_factory,
            $this->logger,
        );
    }

    public function testItDoesNotPushKeysIfUserIsInvalid()
    {
        $this->expectException(ForbiddenException::class);

        $this->user_account_manager->expects($this->never())->method('pushSSHKeys');

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItLogsAnErrorIfSSHKeyPushFails(): void
    {
        $this->user_account_manager->method('pushSSHKeys')->willThrowException(new Git_UserSynchronisationException());

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItAddsResponseFeedbackIfSSHKeyPushFails(): void
    {
        $this->user_account_manager->method('pushSSHKeys')->willThrowException((new Git_UserSynchronisationException()));

        $this->gerrit_server_factory->method('getRemoteServersForUser')->willReturn([]);

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
        self::assertCount(1, $feedback);
        self::assertEquals(Feedback::ERROR, $feedback[0]['level']);
    }
}
