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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
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

final class AppRevocationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var AppRevocationController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationRevoker
     */
    private $autorization_revoker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|RedirectWithFeedbackFactory
     */
    private $redirector;

    protected function setUp(): void
    {
        $csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $csrf_token->shouldReceive('check');
        $this->user_manager         = M::mock(\UserManager::class);
        $this->autorization_revoker = M::mock(AuthorizationRevoker::class);
        $this->redirector           = M::mock(RedirectWithFeedbackFactory::class);
        $this->controller           = new AppRevocationController(
            HTTPFactoryBuilder::responseFactory(),
            $csrf_token,
            $this->user_manager,
            $this->autorization_revoker,
            $this->redirector,
            M::mock(EmitterInterface::class)
        );
    }

    public function testHandleRedirectsWhenUserIsAnonymous(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn(UserTestBuilder::anAnonymousUser()->build());
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(AccountAppsController::URL, $response->getHeaderLine('Location'));
    }

    public function testHandleRedirectsWithErrorWhenNoAppIdInBody(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $request = (new NullServerRequest())->withAttribute(BaseLayout::class, LayoutBuilder::build());
        $this->redirector->shouldReceive('createResponseForUser')
            ->with($user, AccountAppsController::URL, M::type(NewFeedback::class))
            ->once()
            ->andReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testHandleRedirectsWithErrorIfNoAuthorizationFoundForUserAndAppID(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);
        $this->autorization_revoker->shouldReceive('doesAuthorizationExist')
            ->with($user, 53)
            ->once()
            ->andReturnFalse();
        $this->redirector->shouldReceive('createResponseForUser')
            ->with($user, AccountAppsController::URL, M::type(NewFeedback::class))
            ->once()
            ->andReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testHandleRevokesAuthorizationAndRedirects(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $inspector = new LayoutInspector();
        $request   = (new NullServerRequest())->withAttribute(
            BaseLayout::class,
            LayoutBuilder::buildWithInspector($inspector)
        )->withParsedBody(['app_id' => '53']);
        $this->autorization_revoker->shouldReceive('doesAuthorizationExist')
            ->with($user, 53)
            ->once()
            ->andReturnTrue();
        $this->autorization_revoker->shouldReceive('revokeAppAuthorization')->with($user, 53)->once();
        $this->redirector->shouldReceive('createResponseForUser')
            ->with($user, AccountAppsController::URL, M::type(NewFeedback::class))
            ->once()
            ->andReturn(HTTPFactoryBuilder::responseFactory()->createResponse(302));

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
    }
}
