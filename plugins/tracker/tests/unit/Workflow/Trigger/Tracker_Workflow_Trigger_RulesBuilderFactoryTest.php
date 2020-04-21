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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Workflow_Trigger_RulesBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker
     */
    private $target_tracker;

    /**
     * @var Tracker_Workflow_Trigger_RulesBuilderFactory
     */
    private $factory;
    private $formelement_factory;

    protected function setUp(): void
    {
        $this->target_tracker = Mockery::mock(Tracker::class);
        $this->target_tracker->shouldReceive('getId')->andReturn(12);
        $this->target_tracker->shouldReceive('getChildren')->andReturn([]);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->factory = new Tracker_Workflow_Trigger_RulesBuilderFactory($this->formelement_factory);
    }

    public function testItHasAllTargetTrackerSelectBoxFields(): void
    {
        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($this->target_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->factory->getForTracker($this->target_tracker);
    }

    public function testItHasAllTriggeringFields(): void
    {
        $child_tracker = Mockery::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(200);
        $target_tracker = Mockery::mock(Tracker::class);
        $target_tracker->shouldReceive('getId')->andReturn(12);
        $target_tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($target_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($child_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->factory->getForTracker($target_tracker);
    }
}
