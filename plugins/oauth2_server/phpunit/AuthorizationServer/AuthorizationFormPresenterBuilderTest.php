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
use Tuleap\OAuth2Server\App\OAuth2App;

final class AuthorizationFormPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuild(): void
    {
        $foobar_definition    = new class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'foo:bar';
            }

            public function getDescription(): string
            {
                return 'Test scope';
            }
        };
        $foobar_scope         = M::mock(AuthenticationScope::class)->shouldReceive('getDefinition')
            ->once()
            ->andReturn($foobar_definition)
            ->getMock();
        $typevalue_definition = new class implements AuthenticationScopeDefinition {
            public function getName(): string
            {
                return 'type:value';
            }

            public function getDescription(): string
            {
                return 'Other test scope';
            }
        };
        $typevalue_scope      = M::mock(AuthenticationScope::class)->shouldReceive('getDefinition')
            ->once()
            ->andReturn($typevalue_definition)
            ->getMock();
        $builder              = new AuthorizationFormPresenterBuilder();
        $presenter            = $builder->build(
            new OAuth2App(
                1,
                'Jenkins',
                'https://example.com',
                new \Project(['group_id' => 101, 'group_name' => 'Test Project'])
            ),
            new Uri('https://example.com?error=access_denied'),
            $foobar_scope,
            $typevalue_scope
        );
        $this->assertContainsEquals(
            new OAuth2ScopeDefinitionPresenter($foobar_definition),
            $presenter->scope_presenters
        );
        $this->assertContainsEquals(
            new OAuth2ScopeDefinitionPresenter($typevalue_definition),
            $presenter->scope_presenters
        );
        $this->assertSame('Jenkins', $presenter->app_name);
        $this->assertSame('Test Project', $presenter->project_name);
    }
}
