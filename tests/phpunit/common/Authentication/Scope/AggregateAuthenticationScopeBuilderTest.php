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

namespace Tuleap\Authentication\Scope;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AggregateAuthenticationScopeBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testStopLookingForAnAuthenticationScopeAtTheFirstSuccessfulAnswerFromABuilder(): void
    {
        $builder_1 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_1->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->andReturn(\Mockery::mock(AuthenticationScope::class));
        $builder_2 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_2->shouldNotReceive('buildAuthenticationScopeFromScopeIdentifier');

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2);

        $this->assertNotNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testAllBuildersAreVerifiedIfTheAuthenticationScopeIsNotFound(): void
    {
        $builder_1 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_1->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->once()->andReturnNull();
        $builder_2 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_2->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->once()->andReturnNull();

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2);

        $this->assertNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testBuildersCanBeRetrievedFromAnEventDispatcher(): void
    {
        $builder = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->once()->andReturn(
            \Mockery::mock(AuthenticationScope::class)
        );

        $event = new class ($builder) implements AuthenticationScopeBuilderCollectorEvent {
            /**
             * @var AuthenticationScopeBuilder
             */
            private $builder;

            public function __construct(AuthenticationScopeBuilder $builder)
            {
                $this->builder = $builder;
            }

            public function getAuthenticationKeyScopeBuilders() : array
            {
                return [$this->builder];
            }
        };

        $event_dispatcher = new class implements EventDispatcherInterface
        {
            public function dispatch(object $event)
            {
                assert($event instanceof AuthenticationScopeBuilderCollectorEvent);
            }
        };

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromEventDispatcher($event_dispatcher, $event);

        $this->assertNotNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testGetAllAccessKeysScopesFromMultipleBuilders(): void
    {
        $scope_1   = \Mockery::mock(AuthenticationScope::class);
        $scope_2   = \Mockery::mock(AuthenticationScope::class);
        $builder_1 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_1->shouldReceive('buildAllAvailableAuthenticationScopes')->andReturn([$scope_1, $scope_2]);
        $builder_2 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $scope_3   = \Mockery::mock(AuthenticationScope::class);
        $builder_2->shouldReceive('buildAllAvailableAuthenticationScopes')->andReturn([$scope_3]);
        $builder_3 = \Mockery::mock(AuthenticationScopeBuilder::class);
        $builder_3->shouldReceive('buildAllAvailableAuthenticationScopes')->andReturn([]);

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2, $builder_3);

        $this->assertEqualsCanonicalizing(
            [$scope_1, $scope_2, $scope_3],
            $aggregate_builder->buildAllAvailableAuthenticationScopes()
        );
    }

    public function testGetEmptySetOfScopesWhenThereIsNoBuilder(): void
    {
        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList();

        $this->assertEmpty($aggregate_builder->buildAllAvailableAuthenticationScopes());
    }
}
