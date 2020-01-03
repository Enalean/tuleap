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

namespace Tuleap\User\AccessKey\Scope;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AggregateAccessKeyScopeBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testStopLookingForAnAccessKeyScopeAtTheFirstSuccessfulAnswerFromABuilder(): void
    {
        $builder_1 = \Mockery::mock(AccessKeyScopeBuilder::class);
        $builder_1->shouldReceive('buildAccessKeyScopeFromScopeIdentifier')->andReturn(\Mockery::mock(AccessKeyScope::class));
        $builder_2 = \Mockery::mock(AccessKeyScopeBuilder::class);
        $builder_2->shouldNotReceive('buildAccessKeyScopeFromScopeIdentifier');

        $aggregate_builder = AggregateAccessKeyScopeBuilder::fromBuildersList($builder_1, $builder_2);

        $this->assertNotNull(
            $aggregate_builder->buildAccessKeyScopeFromScopeIdentifier(
                AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testAllBuildersAreVerifiedIfTheAccessKeyScopeIsNotFound(): void
    {
        $builder_1 = \Mockery::mock(AccessKeyScopeBuilder::class);
        $builder_1->shouldReceive('buildAccessKeyScopeFromScopeIdentifier')->once()->andReturnNull();
        $builder_2 = \Mockery::mock(AccessKeyScopeBuilder::class);
        $builder_2->shouldReceive('buildAccessKeyScopeFromScopeIdentifier')->once()->andReturnNull();

        $aggregate_builder = AggregateAccessKeyScopeBuilder::fromBuildersList($builder_1, $builder_2);

        $this->assertNull(
            $aggregate_builder->buildAccessKeyScopeFromScopeIdentifier(
                AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }

    public function testBuildersCanBeRetrievedFromAnEventDispatcher(): void
    {
        $event_dispatcher = new class implements EventDispatcherInterface
        {
            public function dispatch(object $event)
            {
                assert($event instanceof AccessKeyScopeBuilderCollector);

                $builder = \Mockery::mock(AccessKeyScopeBuilder::class);
                $builder->shouldReceive('buildAccessKeyScopeFromScopeIdentifier')->once()->andReturn(
                    \Mockery::mock(AccessKeyScope::class)
                );

                $event->addAccessKeyScopeBuilder($builder);
            }
        };


        $aggregate_builder = AggregateAccessKeyScopeBuilder::fromEventDispatcher($event_dispatcher);

        $this->assertNotNull(
            $aggregate_builder->buildAccessKeyScopeFromScopeIdentifier(
                AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar')
            )
        );
    }
}
