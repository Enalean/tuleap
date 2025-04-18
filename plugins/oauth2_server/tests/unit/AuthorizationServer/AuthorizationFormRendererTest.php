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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use GuzzleHttp\Psr7\Uri;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Stubs\CSRF\CSRFSessionKeyStorageStub;
use Tuleap\Test\Stubs\CSRF\CSRFSigningKeyStorageStub;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationFormRendererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    /** @var AuthorizationFormRenderer */
    private $form_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationFormPresenterBuilder
     */
    private $presenter_builder;

    protected function setUp(): void
    {
        $this->presenter_builder = $this->createMock(AuthorizationFormPresenterBuilder::class);
        $this->form_renderer     = new AuthorizationFormRenderer(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->presenter_builder
        );
    }

    public function testRenderForm(): void
    {
        $foobar_scope         = $this->createMock(AuthenticationScope::class);
        $foobar_definition    = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Foo Bar';
            }

            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $typevalue_scope      = $this->createMock(AuthenticationScope::class);
        $typevalue_definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Type Value';
            }

            public function getDescription(): string
            {
                return 'Other test scope';
            }
        };
        $redirect_uri         = 'https://example.com/redirect';
        $form_data            = new AuthorizationFormData(
            new OAuth2App(
                1,
                'Jenkins',
                $redirect_uri,
                true,
                new \Project(['group_id' => 101, 'group_name' => 'Test Project'])
            ),
            new \CSRFSynchronizerToken('some_url', 'token_name', new CSRFSigningKeyStorageStub(), new CSRFSessionKeyStorageStub()),
            $redirect_uri,
            'xyz',
            'pkce_chall',
            'oidc_nonce',
            $foobar_scope,
            $typevalue_scope
        );
        $this->presenter_builder->expects($this->once())->method('build')
            ->willReturn(
                new AuthorizationFormPresenter(
                    $form_data,
                    new Uri($redirect_uri),
                    [
                        new OAuth2ScopeDefinitionPresenter($foobar_definition),
                        new OAuth2ScopeDefinitionPresenter($typevalue_definition),
                    ],
                    [
                        new OAuth2ScopeIdentifierPresenter(OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar')),
                        new OAuth2ScopeIdentifierPresenter(OAuth2ScopeIdentifier::fromIdentifierKey('type:value')),
                    ]
                )
            );

        $response = $this->form_renderer->renderForm($form_data, LayoutBuilder::build());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Authorize application', $response->getBody()->getContents());
    }
}
