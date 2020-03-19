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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class AuthorizationFormRendererTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /** @var AuthorizationFormRenderer */
    private $form_renderer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationFormPresenterBuilder
     */
    private $presenter_builder;

    protected function setUp(): void
    {
        $this->presenter_builder = M::mock(AuthorizationFormPresenterBuilder::class);
        $this->form_renderer     = new AuthorizationFormRenderer(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->presenter_builder
        );
    }

    public function testRenderForm(): void
    {
        $foobar_scope         = M::mock(AuthenticationScope::class);
        $foobar_definition    = new class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Foo Bar';
            }

            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $typevalue_scope      = M::mock(AuthenticationScope::class);
        $typevalue_definition = new class implements AuthenticationScopeDefinition {
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
            M::mock(\CSRFSynchronizerToken::class),
            $redirect_uri,
            'xyz',
            'pkce_chall',
            $foobar_scope,
            $typevalue_scope
        );
        $this->presenter_builder->shouldReceive('build')
            ->once()
            ->andReturn(
                new AuthorizationFormPresenter(
                    $form_data,
                    new Uri($redirect_uri),
                    [
                        new OAuth2ScopeDefinitionPresenter($foobar_definition),
                        new OAuth2ScopeDefinitionPresenter($typevalue_definition)
                    ],
                    [
                        new OAuth2ScopeIdentifierPresenter(OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar')),
                        new OAuth2ScopeIdentifierPresenter(OAuth2ScopeIdentifier::fromIdentifierKey('type:value'))
                    ]
                )
            );

        $response = $this->form_renderer->renderForm($form_data, LayoutBuilder::build());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Authorize application', $response->getBody()->getContents());
    }
}
