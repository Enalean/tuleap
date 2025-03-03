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
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\ClientSecretUpdater;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class NewClientSecretControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AppProjectVerifier
     */
    private $project_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientSecretUpdater
     */
    private $client_secret_updater;
    /**
     * @var NewClientSecretController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->redirector            = $this->createMock(RedirectWithFeedbackFactory::class);
        $this->project_verifier      = $this->createMock(OAuth2AppProjectVerifier::class);
        $this->client_secret_updater = $this->createMock(ClientSecretUpdater::class);
        $csrf_token                  = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller            = new NewClientSecretController(
            HTTPFactoryBuilder::responseFactory(),
            $this->redirector,
            $this->project_verifier,
            $this->client_secret_updater,
            $csrf_token,
            $this->createMock(EmitterInterface::class)
        );
        $csrf_token->method('check');
    }

    public function testGetProjectAdminUrl(): void
    {
        $project = new \Project(['group_id' => 102]);
        self::assertSame(
            '/plugins/oauth2_server/project/102/admin/new-client-secret',
            NewClientSecretController::getProjectAdminURL($project)
        );
    }

    /**
     * @param array|null $parsed_body
     */
    #[DataProvider('dataProviderInvalidBody')]
    public function testHandleRedirectsWithErrorWhenDataIsInvalid($parsed_body): void
    {
        $request  = $this->buildProjectAdminRequest()->withParsedBody($parsed_body);
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->redirector->expects(self::once())->method('createResponseForUser')
            ->with(self::isInstanceOf(\PFUser::class), '/plugins/oauth2_server/project/102/admin', self::isInstanceOf(NewFeedback::class))
            ->willReturn($response);
        $this->client_secret_updater->expects(self::never())->method('updateClientSecret');

        self::assertSame($response, $this->controller->handle($request));
    }

    public static function dataProviderInvalidBody(): array
    {
        return [
            'No body'   => [null],
            'No app id' => [['not_app_id' => '12']],
        ];
    }

    public function testHandleProjectAdminGeneratesClientSecretAndRedirects(): void
    {
        $request = $this->buildProjectAdminRequest()->withParsedBody(['app_id' => '45']);
        $this->project_verifier->method('isAppPartOfTheExpectedProject')->willReturn(true);
        $this->client_secret_updater->expects(self::once())->method('updateClientSecret')->with(45);

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/project/102/admin', $response->getHeaderLine('Location'));
    }

    public function testHandleSiteAdminGeneratesClientSecretAndRedirects(): void
    {
        $request = $this->buildSiteAdminRequest()->withParsedBody(['app_id' => '45']);
        $this->project_verifier->method('isASiteLevelApp')->willReturn(true);
        $this->client_secret_updater->expects(self::once())->method('updateClientSecret')->with(45);

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/admin', $response->getHeaderLine('Location'));
    }

    public function testRejectsGenerationOfNewSecretForAnAppOfAnotherProject(): void
    {
        $this->project_verifier->method('isAppPartOfTheExpectedProject')->willReturn(false);

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
