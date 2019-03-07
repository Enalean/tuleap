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

declare(strict_types=1);

namespace Tuleap\Baseline\Stub;

use Tracker_Artifact;
use Tuleap\Baseline\MilestoneRepository;

/**
 * In memory implementation of MilestoneRepository used for tests
 */
class MilestoneRepositoryStub implements MilestoneRepository
{
    /** @var Tracker_Artifact[] */
    private $milestones_by_id = [];

    public function add(Tracker_Artifact $milestone): void
    {
        $this->milestones_by_id [$milestone->getId()] = $milestone;
    }

    public function findById(int $id): ?Tracker_Artifact
    {
        return $this->milestones_by_id[$id] ?? null;
    }

    public function removeAll()
    {
        $this->milestones_by_id = [];
    }
}
