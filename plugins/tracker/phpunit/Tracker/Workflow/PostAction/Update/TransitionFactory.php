<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Mockery;
use Mockery\MockInterface;
use Transition;

class TransitionFactory
{
    public static function buildATransition(): MockInterface
    {
        $transition = Mockery::mock(Transition::class);
        $transition->shouldReceive('getId')
            ->andReturn(1)
            ->byDefault();
        return $transition;
    }

    public static function buildATransitionWithId(int $id): MockInterface
    {
        $transition = TransitionFactory::buildATransition();
        $transition->shouldReceive('getId')
            ->andReturn($id);
        return $transition;
    }

    public static function buildATransitionWithTracker(\Tracker $tracker): MockInterface
    {
        $transition = self::buildATransition();
        $workflow      = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('getTracker')
            ->andReturn($tracker);
        $transition->shouldReceive('getWorkflow')
            ->andReturn($workflow);
        return $transition;
    }
}
