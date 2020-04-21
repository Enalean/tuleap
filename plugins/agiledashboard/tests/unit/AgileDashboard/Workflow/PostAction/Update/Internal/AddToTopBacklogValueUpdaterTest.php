<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Transition;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class AddToTopBacklogValueUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AddToTopBacklogValueUpdater
     */
    private $value_updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogValueRepository
     */
    private $value_repository;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostActionCollection
     */
    private $collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Transition
     */
    private $transition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->value_repository = Mockery::mock(AddToTopBacklogValueRepository::class);

        $this->value_updater = new AddToTopBacklogValueUpdater(
            $this->value_repository
        );

        $this->collection = Mockery::mock(PostActionCollection::class);
        $this->transition = Mockery::mock(Transition::class);
    }

    public function testItAddsOnlyOneAddToTopBacklogPostAction(): void
    {
        $this->collection->shouldReceive('getExternalPostActionsValue')
            ->once()
            ->andReturn([
                new AddToTopBacklogValue(),
                new AddToTopBacklogValue()
            ]);

        $this->value_repository->shouldReceive('deleteAllByTransition')->once();
        $this->value_repository->shouldReceive('create')->once();

        $this->value_updater->updateByTransition(
            $this->collection,
            $this->transition
        );
    }

    public function testItOnlyDeletesAddToTopBacklogPostActionIfNoActionProvided(): void
    {
        $this->collection->shouldReceive('getExternalPostActionsValue')
            ->once()
            ->andReturn([]);

        $this->value_repository->shouldReceive('deleteAllByTransition')->once();
        $this->value_repository->shouldReceive('create')->never();

        $this->value_updater->updateByTransition(
            $this->collection,
            $this->transition
        );
    }
}
