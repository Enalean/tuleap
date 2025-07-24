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

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationFormPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AuthorizationFormPresenterBuilder
     */
    private $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new AuthorizationFormPresenterBuilder(
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory())
        );
    }

    public function testBuild(): void
    {
        $foobar_identifier = OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar');
        $foobar_definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            #[\Override]
            public function getName(): string
            {
                return 'Foo Bar';
            }

            #[\Override]
            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $foobar_scope      = $this->createMock(AuthenticationScope::class);
        $foobar_scope->expects($this->once())->method('getDefinition')->willReturn($foobar_definition);
        $foobar_scope->expects($this->once())->method('getIdentifier')->willReturn($foobar_identifier);
        $typevalue_identifier = OAuth2ScopeIdentifier::fromIdentifierKey('type:value');
        $typevalue_definition = new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
            #[\Override]
            public function getName(): string
            {
                return 'Type Value';
            }

            #[\Override]
            public function getDescription(): string
            {
                return 'Other test scope';
            }
        };
        $typevalue_scope      = $this->createMock(AuthenticationScope::class);
        $typevalue_scope->expects($this->once())->method('getDefinition')->willReturn($typevalue_definition);
        $typevalue_scope->expects($this->once())->method('getIdentifier')->willReturn($typevalue_identifier);
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
            $this->createMock(\CSRFSynchronizerToken::class),
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
        self::assertSame('Jenkins', $presenter->app_name);
        self::assertSame('Test Project', $presenter->project_name);
        self::assertSame($redirect_uri, $presenter->redirect_uri);
        self::assertSame($state_value, $presenter->state);
        self::assertSame(bin2hex($pkce_code_challenge), $presenter->pkce_code_challenge);
        self::assertSame(
            'https://example.com?state=xyz&error=access_denied',
            (string) $presenter->deny_authorization_uri
        );
    }
}
