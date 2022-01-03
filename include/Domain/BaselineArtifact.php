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

namespace Tuleap\Baseline\Domain;

use Project;

class BaselineArtifact
{
    /** @var int */
    private $id;

    /** @var string|null */
    private $title;

    /** @var string|null */
    private $description;

    /** @var int|null */
    private $initial_effort;

    /** @var string|null */
    private $status;

    /** @var Project */
    private $project;

    /**  @var int */
    private $tracker_id;

    /**  @var string */
    private $tracker_name;

    /** @var int[] */
    private $linked_artifact_ids;

    /**
     * @param int[] $linked_artifact_ids
     */
    public function __construct(
        int $id,
        ?string $title,
        ?string $description,
        ?int $initial_effort,
        ?string $status,
        Project $project,
        int $tracker_id,
        string $tracker_name,
        array $linked_artifact_ids,
    ) {
        $this->id                  = $id;
        $this->title               = $title;
        $this->description         = $description;
        $this->initial_effort      = $initial_effort;
        $this->status              = $status;
        $this->project             = $project;
        $this->tracker_id          = $tracker_id;
        $this->tracker_name        = $tracker_name;
        $this->linked_artifact_ids = $linked_artifact_ids;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getInitialEffort(): ?int
    {
        return $this->initial_effort;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getTrackerId(): int
    {
        return $this->tracker_id;
    }

    public function getTrackerName(): string
    {
        return $this->tracker_name;
    }

    /**
     * @return int[]
     */
    public function getLinkedArtifactIds(): array
    {
        return $this->linked_artifact_ids;
    }

    public function equals(BaselineArtifact $artifact): bool
    {
        return $this->id === $artifact->getId();
    }
}
