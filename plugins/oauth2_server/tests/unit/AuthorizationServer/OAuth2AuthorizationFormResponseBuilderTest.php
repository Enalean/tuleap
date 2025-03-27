<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AuthorizationFormResponseBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItBuildsAuthorizationFormResponse(): void
    {
        $form_renderer = $this->createMock(AuthorizationFormRenderer::class);
        $form_renderer->expects($this->once())->method('renderForm')->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse());

        $form_response_builder = new OAuth2ConsentRequiredResponseBuilder(
            $form_renderer
        );

        $request  = (new NullServerRequest())->withAttribute(BaseLayout::class, LayoutBuilder::build());
        $project  = ProjectTestBuilder::aProject()->withPublicName('Test Project')->build();
        $scopes   = [$this->createMock(AuthenticationScope::class)];
        $response = $form_response_builder->buildConsentRequiredResponse(
            $request,
            new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project),
            'https://example.com/redirect',
            'xyz',
            null,
            null,
            $scopes
        );

        $content_security_policy = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("form-action 'self' https://example.com/redirect;", $content_security_policy);
    }
}
