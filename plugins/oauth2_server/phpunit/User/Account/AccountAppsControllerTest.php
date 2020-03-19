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
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\AccountTabPresenterCollection;

final class AccountAppsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /**
     * @var AccountAppsController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->presenter_builder = M::mock(AppsPresenterBuilder::class);
        $this->user_manager      = M::mock(\UserManager::class);
        $this->controller        = new AccountAppsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->presenter_builder,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->user_manager,
            M::mock(EmitterInterface::class)
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testHandleForbidsAnonymousUsers(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle(new NullServerRequest());
    }

    public function testHandleRendersAccountApps(): void
    {
        $user = UserTestBuilder::aUser()->withId(101)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);
        $csrf_presenter = CSRFSynchronizerTokenPresenter::fromToken(AccountAppsController::getCSRFToken());

        $this->presenter_builder->shouldReceive('build')
            ->with($user, M::type(CSRFSynchronizerTokenPresenter::class))
            ->once()
            ->andReturn(new AppsPresenter($csrf_presenter, M::mock(AccountTabPresenterCollection::class)));

        $response = $this->controller->handle(
            (new NullServerRequest())->withAttribute(BaseLayout::class, LayoutBuilder::build())
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('OAuth2 Apps', $response->getBody()->getContents());
    }
}
