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

use Psr\EventDispatcher\EventDispatcherInterface;

final class AggregateAuthenticationScopeBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testStopLookingForAnAuthenticationScopeAtTheFirstSuccessfulAnswerFromABuilder(): void
    {
        $builder_1 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_1->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn($this->createMock(AuthenticationScope::class));
        $builder_2 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_2->expects(self::never())->method('buildAuthenticationScopeFromScopeIdentifier');

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2);

        self::assertNotNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testAllBuildersAreVerifiedIfTheAuthenticationScopeIsNotFound(): void
    {
        $builder_1 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_1->expects(self::once())->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(null);
        $builder_2 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_2->expects(self::once())->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(null);

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2);

        self::assertNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testBuildersCanBeRetrievedFromAnEventDispatcher(): void
    {
        $builder = $this->createMock(AuthenticationScopeBuilder::class);
        $builder->expects(self::once())->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(
            $this->createMock(AuthenticationScope::class)
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

            public function getAuthenticationKeyScopeBuilders(): array
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

        self::assertNotNull(
            $aggregate_builder->buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testGetAllAccessKeysScopesFromMultipleBuilders(): void
    {
        $scope_1   = $this->createMock(AuthenticationScope::class);
        $scope_2   = $this->createMock(AuthenticationScope::class);
        $builder_1 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_1->method('buildAllAvailableAuthenticationScopes')->willReturn([$scope_1, $scope_2]);
        $builder_2 = $this->createMock(AuthenticationScopeBuilder::class);
        $scope_3   = $this->createMock(AuthenticationScope::class);
        $builder_2->method('buildAllAvailableAuthenticationScopes')->willReturn([$scope_3]);
        $builder_3 = $this->createMock(AuthenticationScopeBuilder::class);
        $builder_3->method('buildAllAvailableAuthenticationScopes')->willReturn([]);

        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList($builder_1, $builder_2, $builder_3);

        self::assertEqualsCanonicalizing(
            [$scope_1, $scope_2, $scope_3],
            $aggregate_builder->buildAllAvailableAuthenticationScopes()
        );
    }

    public function testGetEmptySetOfScopesWhenThereIsNoBuilder(): void
    {
        $aggregate_builder = AggregateAuthenticationScopeBuilder::fromBuildersList();

        self::assertEmpty($aggregate_builder->buildAllAvailableAuthenticationScopes());
    }
}
