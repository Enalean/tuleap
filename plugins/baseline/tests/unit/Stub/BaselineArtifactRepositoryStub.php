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
use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\Clock;
use Tuleap\Baseline\Domain\UserIdentifier;

/**
 * In memory implementation of BaselineArtifactRepository used for tests
 */
class BaselineArtifactRepositoryStub implements BaselineArtifactRepository
{
    /** @var ArtifactHistory[] */
    private $artifact_histories_by_id = [];

    /** @var Clock */
    private $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * Add new artifact version at given date
     */
    public function addAt(BaselineArtifact $artifact, DateTimeInterface $date): void
    {
        $history = $this->findHistoryOrCreateNewOne($artifact);
        $history->add($artifact, $date);
    }

    #[\Override]
    public function findById(UserIdentifier $current_user, int $id): ?BaselineArtifact
    {
        $history = $this->artifact_histories_by_id[$id];
        if ($history === null) {
            return null;
        }
        return $history->findAt($this->clock->now());
    }

    #[\Override]
    public function findByIdAt(UserIdentifier $current_user, int $id, DateTimeInterface $date): ?BaselineArtifact
    {
        $history = $this->artifact_histories_by_id[$id];
        \assert($history instanceof ArtifactHistory);
        if ($history === null) {
            return null;
        }
        return $history->findAt($date);
    }

    public function removeAll(): void
    {
        $this->artifact_histories_by_id = [];
    }

    private function findHistoryOrCreateNewOne(BaselineArtifact $artifact): ArtifactHistory
    {
        $artifact_id = $artifact->getId();
        if (! isset($this->artifact_histories_by_id[$artifact_id])) {
            $this->artifact_histories_by_id[$artifact_id] = new ArtifactHistory();
        }
        return $this->artifact_histories_by_id[$artifact_id];
    }
}
