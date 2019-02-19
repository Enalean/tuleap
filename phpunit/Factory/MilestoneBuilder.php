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

namespace Tuleap\Baseline\Factory;

use Mockery;
use Mockery\MockInterface;
use Project;
use Tracker;
use Tracker_Artifact;

class MilestoneBuilder
{
    /** @var Tracker_Artifact|MockInterface */
    private $milestone;

    /**
     * MilestoneBuilder constructor.
     */
    public function __construct()
    {
        $this->milestone = Mockery::mock(Tracker_Artifact::class);

        $tracker = Mockery::mock(Tracker::class);
        $tracker
            ->shouldReceive('getProject')
            ->andReturn(Mockery::mock(Project::class));
        $this->milestone
            ->shouldReceive('getTracker')
            ->andReturn($tracker)
            ->byDefault();
    }

    public function id(int $id): self
    {
        $this->milestone->shouldReceive('getId')
            ->andReturn($id)
            ->byDefault();
        return $this;
    }

    /**
     * @return Tracker_Artifact|MockInterface
     */
    public function build()
    {
        return $this->milestone;
    }
}
