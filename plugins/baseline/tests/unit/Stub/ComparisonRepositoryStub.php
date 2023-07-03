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

namespace Tuleap\Baseline\Stub;

use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\Clock;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\TransientComparison;
use Tuleap\Baseline\Domain\UserIdentifier;

class ComparisonRepositoryStub implements ComparisonRepository
{
    /** @var Clock */
    private $clock;

    /** @var Comparison[] */
    private $comparisons_by_id = [];

    /** @var int */
    private $id_sequence = 1;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function add(TransientComparison $transient_comparison, UserIdentifier $current_user): Comparison
    {
        $comparison = new Comparison(
            $this->id_sequence++,
            $transient_comparison->getName(),
            $transient_comparison->getComment(),
            $transient_comparison->getBaseBaseline(),
            $transient_comparison->getComparedToBaseline(),
            $current_user,
            $this->clock->now()
        );

        $this->comparisons_by_id[$comparison->getId()] = $comparison;
        return $comparison;
    }

    public function findById(UserIdentifier $current_user, int $id): ?Comparison
    {
        if (! isset($this->comparisons_by_id[$id])) {
            return null;
        }
        return $this->comparisons_by_id[$id];
    }

    public function count(): int
    {
        return count($this->comparisons_by_id);
    }

    public function findAny(): ?Comparison
    {
        if (count($this->comparisons_by_id) === 0) {
            return null;
        }
        return array_values($this->comparisons_by_id)[0];
    }

    /**
     * @return Comparison[]
     */
    public function findByProject(UserIdentifier $current_user, ProjectIdentifier $project, int $page_size, int $comparison_offset): array
    {
        $matching_comparisons = array_filter(
            $this->comparisons_by_id,
            function (Comparison $comparison) use ($project) {
                return $comparison->getProject() === $project;
            }
        );
        return array_slice($matching_comparisons, $comparison_offset, $page_size);
    }

    public function countByProject(ProjectIdentifier $project): int
    {
        return count($this->comparisons_by_id);
    }

    /**
     * It returns a array of comparisons with id as key
     * @return Comparison[]
     */
    public function findAllById(): array
    {
        return $this->comparisons_by_id;
    }

    public function delete(Comparison $comparison, UserIdentifier $current_user): void
    {
        unset($this->comparisons_by_id[$comparison->getId()]);
    }

    public function countByBaseline(Baseline $baseline): int
    {
        return count(
            array_filter(
                $this->comparisons_by_id,
                function (Comparison $comparison) use ($baseline) {
                    return $comparison->getBaseBaseline() === $baseline
                        || $comparison->getComparedToBaseline() === $baseline;
                }
            )
        );
    }
}
