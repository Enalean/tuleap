<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesBuilderFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private Tracker_Workflow_Trigger_RulesBuilderFactory $factory;
    private Tracker_FormElementFactory&MockObject $formelement_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->factory = new Tracker_Workflow_Trigger_RulesBuilderFactory($this->formelement_factory);
    }

    public function testItHasAllTargetTrackerSelectBoxFields(): void
    {
        $target_tracker = TrackerTestBuilder::aTracker()->withId(12)->build();
        $target_tracker->setChildren([]);

        $this->formelement_factory->expects($this->once())->method('getUsedStaticSbFields')
            ->with($target_tracker)
            ->willReturn($this->createMock(DataAccessResult::class));

        $this->factory->getForTracker($target_tracker);
    }

    public function testItHasAllTriggeringFields(): void
    {
        $child_tracker  = TrackerTestBuilder::aTracker()->withId(200)->build();
        $target_tracker = TrackerTestBuilder::aTracker()->withId(12)->build();
        $target_tracker->setChildren([$child_tracker]);

        $this->formelement_factory->expects($this->exactly(2))->method('getUsedStaticSbFields')
            ->willReturnCallback(fn (Tracker $tracker) => match ($tracker) {
                $target_tracker => $this->createMock(DataAccessResult::class),
                $child_tracker => $this->createMock(DataAccessResult::class),
            });

        $this->factory->getForTracker($target_tracker);
    }
}
