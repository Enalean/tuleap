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

namespace Tuleap\Baseline\Stub;

use Tracker_Artifact;
use Tuleap\Baseline\MilestoneRepository;

/**
 * In memory implementation of MilestoneRepository used for tests
 */
class MilestoneRepositoryStub implements MilestoneRepository
{
    /** @var Tracker_Artifact[] */
    private $milestones = [];

    public function add(Tracker_Artifact $milestone): void
    {
        $this->milestones [] = $milestone;
    }

    public function findById(int $id): ?Tracker_Artifact
    {
        $matching_milestones = array_filter(
            $this->milestones,
            function (Tracker_Artifact $milestone) use ($id) {
                return $milestone->getId() === $id;
            }
        );
        if (count($matching_milestones) === 0) {
            return null;
        }
        return $matching_milestones[0];
    }

    public function removeAll()
    {
        $this->milestones = [];
    }
}
