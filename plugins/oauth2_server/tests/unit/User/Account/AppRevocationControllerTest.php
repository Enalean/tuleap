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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\User\Account;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\User\AuthorizationRevoker;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;

final class AppRevocationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var AppRevocationController
     */
    private $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationRevoker
     */
    private $autorization_revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RedirectWithFeedbackFactory
     */
    private $redirector;

    protected function setUp(): void
    {
        $csrf_token = $this->createMock(\CSRFSynchronizerToken::class);
        $csrf_token->method('check');
        $this->user_manager         = $this->createMock(\UserManager::class);
        $this->autorization_revoker = $this->createMock(AuthorizationRevoker::class);
        $this->redirector           = $this->createMock(RedirectWithFeedbackFactory::class);
        $this->controller           = new AppRevocationController(
            HTTPFactoryBuilder::responseFactory(),
            $csrf_token,
            $this->user_manager,
            $this->autorization_revoker,
            $this->redirector,
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testHandleRedirectsWhenUserIsAnonymous(): void
    {
        $this->user_manager->expects(self::once())->method('getCurrentUser')
            ->willReturn(UserTestBuilder::anAnonymousUser()->build());
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);

        $response = $this->controller->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame(AccountAppsController::URL, $response->getHeaderLine('Location'));
    }

    public function testHandleRedirectsWithErrorWhenNoAppIdInBody(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);
        $request = (new NullServerRequest())->withAttribute(BaseLayout::class, LayoutBuilder::build());
        $this->redirector->expects(self::once())->method('createResponseForUser')
            ->with($user, AccountAppsController::URL, self::isInstanceOf(NewFeedback::class))
            ->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        self::assertSame(302, $response->getStatusCode());
    }

    public function testHandleRedirectsWithErrorIfNoAuthorizationFoundForUserAndAppID(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);
        $this->autorization_revoker->expects(self::once())->method('doesAuthorizationExist')
            ->with($user, 53)
            ->willReturn(false);
        $this->redirector->expects(self::once())->method('createResponseForUser')
            ->with($user, AccountAppsController::URL, self::isInstanceOf(NewFeedback::class))
            ->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        self::assertSame(302, $response->getStatusCode());
    }

    public function testHandleRevokesAuthorizationAndRedirects(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);
        $this->autorization_revoker->expects(self::once())->method('doesAuthorizationExist')
            ->with($user, 53)
            ->willReturn(true);
        $this->autorization_revoker->expects(self::once())->method('revokeAppAuthorization')->with($user, 53);
        $this->redirector->expects(self::once())->method('createResponseForUser')
            ->with($user, AccountAppsController::URL, self::isInstanceOf(NewFeedback::class))
            ->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        self::assertSame(302, $response->getStatusCode());
    }
}
