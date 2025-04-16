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

namespace Tuleap\OAuth2Server\Administration;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecretStore;
use Tuleap\OAuth2ServerCore\App\NewOAuth2App;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddAppControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AddAppController
     */
    private $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LastGeneratedClientSecretStore
     */
    private $client_secret_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->app_dao             = $this->createMock(AppDao::class);
        $this->client_secret_store = $this->createMock(LastGeneratedClientSecretStore::class);
        $this->redirector          = $this->createMock(RedirectWithFeedbackFactory::class);
        $this->csrf_token          = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller          = new AddAppController(
            HTTPFactoryBuilder::responseFactory(),
            $this->app_dao,
            new SplitTokenVerificationStringHasher(),
            $this->client_secret_store,
            $this->redirector,
            $this->csrf_token,
            $this->createMock(EmitterInterface::class)
        );
        $this->csrf_token->method('check');
    }

    /**
     * @param array|null $parsed_body
     */
    #[DataProvider('dataProviderInvalidBody')]
    public function testHandleRedirectsWithErrorWhenDataIsInvalid($parsed_body): void
    {
        $request  = $this->buildProjectAdminRequest()->withParsedBody($parsed_body);
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->redirector->expects($this->once())->method('createResponseForUser')
            ->with(self::isInstanceOf(\PFUser::class), '/plugins/oauth2_server/project/102/admin', self::isInstanceOf(NewFeedback::class))
            ->willReturn($response);
        $this->app_dao->expects($this->never())->method('create');

        self::assertSame($response, $this->controller->handle($request));
    }

    public static function dataProviderInvalidBody(): array
    {
        return [
            'No body'         => [null],
            'No name'         => [['not_name' => 'Jenkins']],
            'No redirect_uri' => [['name' => 'Jenkins']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidBody')]
    public function testHandleCreatesProjectAppAndRedirects(array $body): void
    {
        $request = $this->buildProjectAdminRequest()->withParsedBody($body);
        $this->app_dao->expects($this->once())->method('create')
            ->with(self::isInstanceOf(NewOAuth2App::class))
            ->willReturn(1);
        $this->client_secret_store->expects($this->once())->method('storeLastGeneratedClientSecret')
            ->with(1, self::isInstanceOf(SplitTokenVerificationString::class));

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/project/102/admin', $response->getHeaderLine('Location'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidBody')]
    public function testHandleCreatesSiteAppAndRedirects(array $body): void
    {
        $request = $this->buildSiteAdminRequest()->withParsedBody($body);
        $this->app_dao->expects($this->once())->method('create')
            ->with(self::isInstanceOf(NewOAuth2App::class))
            ->willReturn(1);
        $this->client_secret_store->expects($this->once())->method('storeLastGeneratedClientSecret')
            ->with(1, self::isInstanceOf(SplitTokenVerificationString::class));

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/admin', $response->getHeaderLine('Location'));
    }

    public static function dataProviderValidBody(): array
    {
        return [
            'With "Use PKCE" checked'    => [['name' => 'Jenkins', 'redirect_uri' => 'https://example.com', 'use_pkce' => 'true']],
            'Without "Use PKCE" checked' => [['name' => 'Jenkins', 'redirect_uri' => 'https://example.com']],
        ];
    }

    public function testProjectAdminGetUrl(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        self::assertSame('/plugins/oauth2_server/project/102/admin/add-app', AddAppController::getProjectAdminURL($project));
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
