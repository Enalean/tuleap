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

namespace Tuleap\Reference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\ByNature\CrossReferenceByNatureInCoreOrganizer;

class CrossReferenceByDirectionPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferencePresenterFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureInCoreOrganizer
     */
    private $core_organizer;
    /**
     * @var CrossReferenceByDirectionPresenterBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->event_dispatcher  = Mockery::mock(EventDispatcherInterface::class);
        $this->reference_manager = Mockery::mock(\ReferenceManager::class);
        $this->factory           = Mockery::mock(CrossReferencePresenterFactory::class);
        $this->core_organizer    = Mockery::mock(CrossReferenceByNatureInCoreOrganizer::class);
        $project_manager         = Mockery::mock(\ProjectManager::class);

        $this->builder = new CrossReferenceByDirectionPresenterBuilder(
            $this->event_dispatcher,
            $this->reference_manager,
            $this->factory,
            $project_manager,
            Mockery::mock(ProjectAccessChecker::class),
            $this->core_organizer,
        );
    }

    public function testBuild()
    {
        $user = Mockery::mock(\PFUser::class);

        $this->factory
            ->shouldReceive('getSourcesOfEntity')
            ->with("PageName", "wiki", 102)
            ->once()
            ->andReturn([]);
        $this->factory
            ->shouldReceive('getTargetsOfEntity')
            ->with("PageName", "wiki", 102)
            ->once()
            ->andReturn([]);

        $available_natures = new NatureCollection();
        $available_natures->addNature('git', new Nature('git', '', 'Git', true));
        $available_natures->addNature('wiki', new Nature('wiki', '', 'Wiki', true));

        $this->reference_manager
            ->shouldReceive('getAvailableNatures')
            ->andReturn($available_natures);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);

        $this->event_dispatcher
            ->shouldReceive('dispatch')
            ->with(Mockery::type(CrossReferenceByNatureOrganizer::class))
            ->andReturn($by_nature_organizer);

        $this->core_organizer->shouldReceive('organizeCoreReferences')->twice();
        $by_nature_organizer->shouldReceive('organizeRemainingCrossReferences')->twice();

        $by_nature_organizer->shouldReceive('getNatures')->twice()->andReturn([]);


        $presenter = $this->builder->build("PageName", "wiki", 102, $user);

        self::assertEquals([], $presenter->sources_by_nature);
        self::assertEquals([], $presenter->targets_by_nature);
        self::assertFalse($presenter->has_target);
        self::assertFalse($presenter->has_source);
    }
}
