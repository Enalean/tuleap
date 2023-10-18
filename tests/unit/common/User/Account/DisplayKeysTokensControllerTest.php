<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplayKeysTokensControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    private DisplayKeysTokensController $controller;
    /**
     * @var MockObject&AccessKeyPresenterBuilder
     */
    private $access_keys_presenter_builder;
    /**
     * @var \SVN_TokenHandler&\PHPUnit\Framework\MockObject\Stub
     */
    private $svn_token_handler;

    public function setUp(): void
    {
        $event_manager                       = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };
        $csrf_token                          = $this->createMock(CSRFSynchronizerToken::class);
        $this->access_keys_presenter_builder = $this->createMock(AccessKeyPresenterBuilder::class);
        $this->svn_token_handler             = $this->createStub(\SVN_TokenHandler::class);
        $svn_tokens_presenter_builder        = new SVNTokensPresenterBuilder(
            $this->svn_token_handler,
            $this->createMock(KeyFactory::class)
        );

        $this->controller = new DisplayKeysTokensController(
            $event_manager,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $csrf_token,
            $this->access_keys_presenter_builder,
            $svn_tokens_presenter_builder,
        );

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItThrowExceptionForAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePageWithPersonalAccessKey(): void
    {
        $this->svn_token_handler->method('getSVNTokensForUser')->willReturn([]);
        $this->access_keys_presenter_builder->method('getForUser')->willReturn(new AccessKeyPresenter([], [], null, ''));

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(110)->build())->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Personal access keys', $output);
    }

    public function testItRendersThePageWithSSHKeys(): void
    {
        $this->svn_token_handler->method('getSVNTokensForUser')->willReturn([]);
        $this->access_keys_presenter_builder->method('getForUser')->willReturn(new AccessKeyPresenter([], [], null, ''));
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $user->setAuthorizedKeys('ssh-rsa AAAAB3Nc/YihtrgL4fvVJHN8boDfZrZXBYZ8xW1Rstzx/j9MEaWyeQy+2FjJwn6nBRlVqrvHZNP5vEoPdejGABJnnyJroCZ71v2/g5QWjwQjaL4YMUZ3sx6eloxF3 someone@example.com');

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();

        self::assertStringContainsString('SSH keys', $output);
        self::assertStringContainsString('ssh-rsa AAAAB3Nc/YihtrgL4fvVJHN8boDfZrZXBYZ8xW1Rst…71v2/g5QWjwQjaL4YMUZ3sx6eloxF3 someone@example.com', $output);
    }

    public function testDoesNotSVNTokenSectionNothingIfNoSVNTokenExists(): void
    {
        $this->svn_token_handler->method('getSVNTokensForUser')->willReturn([]);
        $this->access_keys_presenter_builder->method('getForUser')->willReturn(new AccessKeyPresenter([], [], null, ''));

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(110)->build())->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringNotContainsString('SVN token', $output);
    }

    public function testItRendersThePageWithSVNToken(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('');
        $this->svn_token_handler->method('getSVNTokensForUser')->willReturn([
            new \SVN_Token(UserTestBuilder::aUser()->build(), 101, '', 1, 1, '', ''),
        ]);
        $this->access_keys_presenter_builder->method('getForUser')->willReturn(new AccessKeyPresenter([], [], null, ''));

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(110)->build())->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('SVN token', $output);
    }
}
