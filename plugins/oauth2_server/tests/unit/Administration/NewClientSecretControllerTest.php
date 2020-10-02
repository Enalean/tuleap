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

namespace Tuleap\OAuth2Server\Administration;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\ClientSecretUpdater;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class NewClientSecretControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AppProjectVerifier
     */
    private $project_verifier;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ClientSecretUpdater
     */
    private $client_secret_updater;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;
    /**
     * @var NewClientSecretController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->redirector            = M::mock(RedirectWithFeedbackFactory::class);
        $this->project_verifier      = M::mock(OAuth2AppProjectVerifier::class);
        $this->client_secret_updater = M::mock(ClientSecretUpdater::class);
        $this->csrf_token            = M::mock(\CSRFSynchronizerToken::class);
        $this->controller            = new NewClientSecretController(
            HTTPFactoryBuilder::responseFactory(),
            $this->redirector,
            $this->project_verifier,
            $this->client_secret_updater,
            $this->csrf_token,
            M::mock(EmitterInterface::class)
        );
        $this->csrf_token->shouldReceive('check');
    }

    public function testGetProjectAdminUrl(): void
    {
        $project = new \Project(['group_id' => 102]);
        $this->assertSame(
            '/plugins/oauth2_server/project/102/admin/new-client-secret',
            NewClientSecretController::getProjectAdminURL($project)
        );
    }

    /**
     * @dataProvider dataProviderInvalidBody
     * @param array|null $parsed_body
     */
    public function testHandleRedirectsWithErrorWhenDataIsInvalid($parsed_body): void
    {
        $request  = $this->buildProjectAdminRequest()->withParsedBody($parsed_body);
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->redirector->shouldReceive('createResponseForUser')
            ->with(M::type(\PFUser::class), '/plugins/oauth2_server/project/102/admin', M::type(NewFeedback::class))
            ->once()
            ->andReturn($response);
        $this->client_secret_updater->shouldNotReceive('updateClientSecret');

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function dataProviderInvalidBody(): array
    {
        return [
            'No body'   => [null],
            'No app id' => [['not_app_id' => '12']]
        ];
    }

    public function testHandleProjectAdminGeneratesClientSecretAndRedirects(): void
    {
        $request = $this->buildProjectAdminRequest()->withParsedBody(['app_id' => '45']);
        $this->project_verifier->shouldReceive('isAppPartOfTheExpectedProject')->andReturn(true);
        $this->client_secret_updater->shouldReceive('updateClientSecret')
            ->once()
            ->with(45);

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/project/102/admin', $response->getHeaderLine('Location'));
    }

    public function testHandleSiteAdminGeneratesClientSecretAndRedirects(): void
    {
        $request = $this->buildSiteAdminRequest()->withParsedBody(['app_id' => '45']);
        $this->project_verifier->shouldReceive('isASiteLevelApp')->andReturn(true);
        $this->client_secret_updater->shouldReceive('updateClientSecret')
            ->once()
            ->with(45);

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/admin', $response->getHeaderLine('Location'));
    }

    public function testRejectsGenerationOfNewSecretForAnAppOfAnotherProject(): void
    {
        $this->project_verifier->shouldReceive('isAppPartOfTheExpectedProject')->andReturn(false);

        $request = $this->buildProjectAdminRequest()->withParsedBody(['app_id' => '45']);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    private function buildProjectAdminRequest(): ServerRequestInterface
    {
        return (new NullServerRequest())->withAttribute(\Project::class, new \Project(['group_id' => 102]))
            ->withAttribute(\PFUser::class, UserTestBuilder::aUser()->build());
    }

    private function buildSiteAdminRequest(): ServerRequestInterface
    {
        return (new NullServerRequest())->withAttribute(\PFUser::class, UserTestBuilder::aUser()->build());
    }
}
