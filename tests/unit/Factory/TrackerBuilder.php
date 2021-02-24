<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Baseline\Factory;

use Mockery;
use Mockery\MockInterface;
use Project;
use Tracker;

class TrackerBuilder
{
    /** @var Tracker|MockInterface */
    private $tracker;

    public function __construct()
    {
        $this->tracker = Mockery::mock(Tracker::class);
    }

    public function id(int $id): self
    {
        $this->tracker
            ->shouldReceive('getId')
            ->andReturn($id)
            ->byDefault();
        return $this;
    }

    public function project(Project $project): self
    {
        $this->tracker
            ->shouldReceive('getProject')
            ->andReturn($project)
            ->byDefault();
        return $this;
    }

    public function itemName(string $name): self
    {
        $this->tracker
            ->shouldReceive('getItemName')
            ->andReturn($name)
            ->byDefault();
        return $this;
    }

    /**
     * @return Tracker|MockInterface
     */
    public function build()
    {
        return $this->tracker;
    }
}
