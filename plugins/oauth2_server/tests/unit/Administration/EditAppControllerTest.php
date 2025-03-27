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
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EditAppControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var EditAppController
     */
    private $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RedirectWithFeedbackFactory
     */
    private $redirector;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AppProjectVerifier
     */
    private $project_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;

    protected function setUp(): void
    {
        $this->redirector       = $this->createMock(RedirectWithFeedbackFactory::class);
        $this->project_verifier = $this->createMock(OAuth2AppProjectVerifier::class);
        $this->app_dao          = $this->createMock(AppDao::class);
        $csrf_token             = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller       = new EditAppController(
            HTTPFactoryBuilder::responseFactory(),
            $this->redirector,
            $this->project_verifier,
            $this->app_dao,
            $csrf_token,
            $this->createMock(EmitterInterface::class)
        );
        $csrf_token->method('check');
    }

    public function testGetProjectAdminUrl(): void
    {
        $project = new \Project(['group_id' => 102]);
        self::assertSame('/plugins/oauth2_server/project/102/admin/edit-app', EditAppController::getProjectAdminURL($project));
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
        $this->app_dao->expects(self::never())->method('updateApp');

        self::assertSame($response, $this->controller->handle($request));
    }

    public static function dataProviderInvalidBody(): array
    {
        return [
            'No body'              => [null],
            'Missing app id'       => [['not_app_id' => '12']],
            'Missing app name'     => [['app_id' => '72']],
            'Missing redirect URI' => [['app_id' => '72', 'name' => 'Jenkins']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidBody')]
    public function testHandleUpdatesProjectAppAndRedirects(array $parsed_body): void
    {
        $request = $this->buildProjectAdminRequest()->withParsedBody($parsed_body);
        $this->project_verifier->method('isAppPartOfTheExpectedProject')->willReturn(true);
        $this->app_dao->expects($this->once())->method('updateApp')
            ->with(self::isInstanceOf(OAuth2App::class));

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/project/102/admin', $response->getHeaderLine('Location'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidBody')]
    public function testHandleUpdatesSiteAppAndRedirects(array $parsed_body): void
    {
        $request = $this->buildSiteAdminRequest()->withParsedBody($parsed_body);
        $this->project_verifier->method('isASiteLevelApp')->willReturn(true);
        $this->app_dao->expects($this->once())->method('updateApp')
            ->with(self::isInstanceOf(OAuth2App::class));

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/plugins/oauth2_server/admin', $response->getHeaderLine('Location'));
    }

    public static function dataProviderValidBody(): array
    {
        return [
            'Missing PKCE is assumed to be false' => [
                ['app_id' => '72', 'name' => 'Jenkins', 'redirect_uri' => 'https://example.com/redirect'],
            ],
            'Present PKCE is true'                => [
                ['app_id' => '72', 'name' => 'Jenkins', 'redirect_uri' => 'https://example.com/redirect', 'use_pkce' => '1'],
            ],
        ];
    }

    public function testRejectsUpdatingAppOfAnotherProject(): void
    {
        $this->project_verifier->method('isAppPartOfTheExpectedProject')->willReturn(false);

        $request = $this->buildProjectAdminRequest()->withParsedBody(
            ['app_id' => '72', 'name' => 'Jenkins', 'redirect_uri' => 'https://example.com/redirect', 'use_pkce' => '1']
        );

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
