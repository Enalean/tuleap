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

namespace Tuleap\OAuth2Server\ProjectAdmin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\NewOAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;

final class AddAppControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AddAppController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppDao
     */
    private $app_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|LastCreatedOAuth2AppStore
     */
    private $last_created_app_store;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->app_dao                = M::mock(AppDao::class);
        $this->last_created_app_store = M::mock(LastCreatedOAuth2AppStore::class);
        $this->redirector             = M::mock(RedirectWithFeedbackFactory::class);
        $this->csrf_token             = M::mock(\CSRFSynchronizerToken::class);
        $this->controller             = new AddAppController(
            HTTPFactoryBuilder::responseFactory(),
            $this->app_dao,
            new SplitTokenVerificationStringHasher(),
            $this->last_created_app_store,
            $this->redirector,
            $this->csrf_token,
            M::mock(EmitterInterface::class)
        );
        $this->csrf_token->shouldReceive('check');
    }

    /**
     * @dataProvider dataProviderInvalidBody
     * @param array|null $parsed_body
     */
    public function testHandleRedirectsWithErrorWhenDataIsInvalid($parsed_body): void
    {
        $request  = $this->buildRequest()->withParsedBody($parsed_body);
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->redirector->shouldReceive('createResponseForUser')
            ->with(M::type(\PFUser::class), '/plugins/oauth2_server/project/102/admin', M::type(NewFeedback::class))
            ->once()
            ->andReturn($response);
        $this->app_dao->shouldNotReceive('create');

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function dataProviderInvalidBody(): array
    {
        return [
            'No body'         => [null],
            'No name'         => [['not_name' => 'Jenkins']],
            'No redirect_uri' => [['name' => 'Jenkins']],
        ];
    }

    /**
     * @dataProvider dataProviderValidBody
     */
    public function testHandleCreatesAppAndRedirects(array $body): void
    {
        $request = $this->buildRequest()->withParsedBody($body);
        $this->app_dao->shouldReceive('create')
            ->once()
            ->with(M::type(NewOAuth2App::class))
            ->andReturn(1);
        $this->last_created_app_store->shouldReceive('storeLastCreatedApp')
            ->once()
            ->with(1, M::type(NewOAuth2App::class));

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/project/102/admin', $response->getHeaderLine('Location'));
    }

    public function dataProviderValidBody(): array
    {
        return [
            'With "Use PKCE" checked'    => [['name' => 'Jenkins', 'redirect_uri' => 'https://example.com', 'use_pkce' => 'true']],
            'Without "Use PKCE" checked' => [['name' => 'Jenkins', 'redirect_uri' => 'https://example.com']],
        ];
    }

    public function testGetUrl(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->once()
            ->andReturn(102)
            ->getMock();

        $this->assertSame('/plugins/oauth2_server/project/102/admin/add-app', AddAppController::getUrl($project));
    }

    private function buildRequest(): ServerRequestInterface
    {
        return (new NullServerRequest())->withAttribute(\Project::class, new \Project(['group_id' => 102]))
            ->withAttribute(\PFUser::class, UserTestBuilder::aUser()->build());
    }
}
