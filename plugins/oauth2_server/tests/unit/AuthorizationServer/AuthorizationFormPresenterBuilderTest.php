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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class AuthorizationFormPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AuthorizationFormPresenterBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new AuthorizationFormPresenterBuilder(
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory())
        );
    }

    public function testBuild(): void
    {
        $foobar_identifier = OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar');
        $foobar_definition = new class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'Foo Bar';
            }

            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $foobar_scope      = M::mock(AuthenticationScope::class);
        $foobar_scope->shouldReceive('getDefinition')->once()->andReturn($foobar_definition);
        $foobar_scope->shouldReceive('getIdentifier')->once()->andReturn($foobar_identifier);
        $typevalue_identifier = OAuth2ScopeIdentifier::fromIdentifierKey('type:value');
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
        $typevalue_scope      = M::mock(AuthenticationScope::class);
        $typevalue_scope->shouldReceive('getDefinition')->once()->andReturn($typevalue_definition);
        $typevalue_scope->shouldReceive('getIdentifier')->once()->andReturn($typevalue_identifier);
        $redirect_uri        = 'https://example.com';
        $state_value         = 'xyz';
        $pkce_code_challenge = 'pkce_code_challenge';
        $oidc_nonce          = 'oidc_nonce';

        $form_data = new AuthorizationFormData(
            new OAuth2App(
                1,
                'Jenkins',
                $redirect_uri,
                true,
                new \Project(['group_id' => 101, 'group_name' => 'Test Project'])
            ),
            M::mock(\CSRFSynchronizerToken::class),
            $redirect_uri,
            $state_value,
            $pkce_code_challenge,
            $oidc_nonce,
            $foobar_scope,
            $typevalue_scope
        );
        $presenter = $this->builder->build($form_data);
        $this->assertContainsEquals(
            new OAuth2ScopeDefinitionPresenter($foobar_definition),
            $presenter->scope_definition_presenters
        );
        $this->assertContainsEquals(
            new OAuth2ScopeDefinitionPresenter($typevalue_definition),
            $presenter->scope_definition_presenters
        );
        $this->assertContainsEquals(
            new OAuth2ScopeIdentifierPresenter($foobar_identifier),
            $presenter->scope_identifier_presenters
        );
        $this->assertContainsEquals(
            new OAuth2ScopeIdentifierPresenter($typevalue_identifier),
            $presenter->scope_identifier_presenters
        );
        $this->assertSame('Jenkins', $presenter->app_name);
        $this->assertSame('Test Project', $presenter->project_name);
        $this->assertSame($redirect_uri, $presenter->redirect_uri);
        $this->assertSame($state_value, $presenter->state);
        $this->assertSame(bin2hex($pkce_code_challenge), $presenter->pkce_code_challenge);
        $this->assertNotNull($presenter->csrf_token);
        $this->assertSame(
            'https://example.com?state=xyz&error=access_denied',
            (string) $presenter->deny_authorization_uri
        );
    }
}
