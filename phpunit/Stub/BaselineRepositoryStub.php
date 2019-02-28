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

use DateTime;
use PFUser;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\TransientBaseline;

/**
 * In memory implementation of BaselineRepository used for tests.
 */
class BaselineRepositoryStub implements BaselineRepository
{
    /** @var Baseline[] */
    private $baselines = [];

    /** @var int */
    private $id_sequence = 1;

    public function add(TransientBaseline $baseline, PFUser $current_user, DateTime $creation_date): Baseline
    {
        $baseline           = new Baseline(
            $this->id_sequence++,
            $baseline->getName(),
            $baseline->getMilestone(),
            $current_user,
            $creation_date
        );
        $this->baselines [] = $baseline;
        return $baseline;
    }

    /**
     * @return Baseline[]
     */
    public function findAll(): array
    {
        return $this->baselines;
    }

    public function findAny(): ?Baseline
    {
        if (count($this->baselines) === 0) {
            return null;
        }
        return $this->baselines[0];
    }

    public function count(): int
    {
        return count($this->baselines);
    }
}
