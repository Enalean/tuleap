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

use DateTimeInterface;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\TransientBaseline;
use Tuleap\Baseline\Domain\UserIdentifier;

/**
 * In memory implementation of BaselineRepository used for tests.
 */
class BaselineRepositoryStub implements BaselineRepository
{
    /** @var Baseline[] */
    private $baselines_by_id = [];

    /** @var int */
    private $id_sequence = 1;

    #[\Override]
    public function add(
        TransientBaseline $transient_baseline,
        UserIdentifier $current_user,
        DateTimeInterface $snapshot_date,
    ): Baseline {
        $baseline = new Baseline(
            $this->id_sequence++,
            $transient_baseline->getName(),
            $transient_baseline->getArtifact(),
            $snapshot_date,
            $current_user
        );
        return $this->addBaseline($baseline);
    }

    public function addBaseline(Baseline $baseline): Baseline
    {
        $this->baselines_by_id[$baseline->getId()] = $baseline;
        return $baseline;
    }

    #[\Override]
    public function findById(UserIdentifier $current_user, int $id): ?Baseline
    {
        if (! isset($this->baselines_by_id[$id])) {
            return null;
        }
        return $this->baselines_by_id[$id];
    }

    #[\Override]
    public function delete(Baseline $baseline): void
    {
        unset($this->baselines_by_id[$baseline->getId()]);
    }

    /**
     * It returns a Baseline array with the baseline id as the key
     * @return Baseline[]
     */
    public function findAllById(): array
    {
        return $this->baselines_by_id;
    }

    public function findAny(): ?Baseline
    {
        if (count($this->baselines_by_id) === 0) {
            return null;
        }
        return array_values($this->baselines_by_id)[0];
    }

    public function count(): int
    {
        return count($this->baselines_by_id);
    }

    /**
     * @return Baseline[]
     */
    #[\Override]
    public function findByProject(UserIdentifier $current_user, ProjectIdentifier $project, int $page_size, int $baseline_offset): array
    {
        $matching_baselines = array_filter(
            $this->baselines_by_id,
            function (Baseline $baseline) use ($project) {
                return $baseline->getArtifact()->getProject() === $project;
            }
        );
        return array_slice($matching_baselines, $baseline_offset, $page_size);
    }

    #[\Override]
    public function countByProject(ProjectIdentifier $project): int
    {
        return count($this->baselines_by_id);
    }
}
