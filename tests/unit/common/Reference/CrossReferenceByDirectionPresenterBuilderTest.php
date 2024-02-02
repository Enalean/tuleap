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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\ByNature\CrossReferenceByNatureInCoreOrganizer;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceByDirectionPresenterBuilderTest extends TestCase
{
    private EventDispatcherInterface&MockObject $event_dispatcher;
    private \ReferenceManager&MockObject $reference_manager;
    private CrossReferencePresenterFactory&MockObject $factory;
    private CrossReferenceByNatureInCoreOrganizer&MockObject $core_organizer;
    private CrossReferenceByDirectionPresenterBuilder $builder;

    protected function setUp(): void
    {
        $this->event_dispatcher  = $this->createMock(EventDispatcherInterface::class);
        $this->reference_manager = $this->createMock(\ReferenceManager::class);
        $this->factory           = $this->createMock(CrossReferencePresenterFactory::class);
        $this->core_organizer    = $this->createMock(CrossReferenceByNatureInCoreOrganizer::class);
        $project_manager         = $this->createMock(\ProjectManager::class);

        $this->builder = new CrossReferenceByDirectionPresenterBuilder(
            $this->event_dispatcher,
            $this->reference_manager,
            $this->factory,
            $project_manager,
            $this->createMock(ProjectAccessChecker::class),
            $this->core_organizer,
        );
    }

    public function testBuild(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $this->factory
            ->expects(self::once())
            ->method('getSourcesOfEntity')
            ->with("PageName", "wiki", 102)
            ->willReturn([]);
        $this->factory
            ->expects(self::once())
            ->method('getTargetsOfEntity')
            ->with("PageName", "wiki", 102)
            ->willReturn([]);

        $available_natures = new NatureCollection();
        $available_natures->addNature('git', new Nature('git', '', 'Git', true));
        $available_natures->addNature('wiki', new Nature('wiki', '', 'Wiki', true));

        $this->reference_manager
            ->method('getAvailableNatures')
            ->willReturn($available_natures);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);

        $this->event_dispatcher
            ->method('dispatch')
            ->with(self::isInstanceOf(CrossReferenceByNatureOrganizer::class))
            ->willReturn($by_nature_organizer);

        $this->core_organizer->expects(self::exactly(2))->method('organizeCoreReferences');
        $by_nature_organizer->expects(self::exactly(2))->method('organizeRemainingCrossReferences');

        $by_nature_organizer->expects(self::exactly(2))->method('getNatures')->willReturn([]);


        $presenter = $this->builder->build("PageName", "wiki", 102, $user);

        self::assertEquals([], $presenter->sources_by_nature);
        self::assertEquals([], $presenter->targets_by_nature);
        self::assertFalse($presenter->has_target);
        self::assertFalse($presenter->has_source);
    }
}
