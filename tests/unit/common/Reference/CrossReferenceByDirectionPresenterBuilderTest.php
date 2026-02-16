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
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\ByNature\CrossReferenceByNatureInCoreOrganizer;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceByDirectionPresenterBuilderTest extends TestCase
{
    private \ReferenceManager&Stub $reference_manager;
    private CrossReferencePresenterFactory&MockObject $factory;
    private CrossReferenceByNatureInCoreOrganizer&MockObject $core_organizer;
    private CrossReferenceByDirectionPresenterBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->reference_manager = $this->createStub(\ReferenceManager::class);
        $this->factory           = $this->createMock(CrossReferencePresenterFactory::class);
        $this->core_organizer    = $this->createMock(CrossReferenceByNatureInCoreOrganizer::class);
    }

    public function testBuild(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $this->factory
            ->expects($this->once())
            ->method('getSourcesOfEntity')
            ->with('PageName', 'wiki', 102)
            ->willReturn([]);
        $this->factory
            ->expects($this->once())
            ->method('getTargetsOfEntity')
            ->with('PageName', 'wiki', 102)
            ->willReturn([]);

        $available_natures = new NatureCollection();
        $available_natures->addNature('git', new Nature('git', '', 'Git', true));
        $available_natures->addNature('wiki', new Nature('wiki', '', 'Wiki', true));

        $this->reference_manager
            ->method('getAvailableNatures')
            ->willReturn($available_natures);

        $this->core_organizer->expects($this->exactly(2))->method('organizeCoreReferences');

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->expects($this->exactly(2))->method('organizeRemainingCrossReferences');
        $by_nature_organizer->expects($this->exactly(2))->method('getNatures')->willReturn([]);


        $this->builder = new CrossReferenceByDirectionPresenterBuilder(
            EventDispatcherStub::withCallback(
                function (object $event) use ($by_nature_organizer): object {
                    if ($event instanceof CrossReferenceByNatureOrganizer) {
                        return $by_nature_organizer;
                    }

                    return $event;
                }
            ),
            $this->reference_manager,
            $this->factory,
            $this->createStub(\ProjectManager::class),
            $this->createStub(ProjectAccessChecker::class),
            $this->core_organizer,
        );
        $presenter     = $this->builder->build('PageName', 'wiki', 102, $user);

        self::assertEquals([], $presenter->sources_by_nature);
        self::assertEquals([], $presenter->targets_by_nature);
        self::assertFalse($presenter->has_target);
        self::assertFalse($presenter->has_source);
    }
}
