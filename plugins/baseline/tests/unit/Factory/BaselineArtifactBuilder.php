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

use Project;
use Tuleap\Baseline\Domain\BaselineArtifact;

class BaselineArtifactBuilder
{
    /** @var int */
    private $id;

    /** @var string */
    private $title;

    /** @var string */
    private $description;

    /** @var int */
    private $initial_effort;

    /** @var string */
    private $status;

    /** @var Project */
    private $project;

    /**  @var int */
    private $tracker_id;

    /**  @var string */
    private $tracker_name;

    /** @var int[] */
    private $linked_artifact_ids = [];

    public function id(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function initialEffort(int $initial_effort): self
    {
        $this->initial_effort = $initial_effort;
        return $this;
    }

    public function status(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function project(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function trackerId(int $tracker_id): self
    {
        $this->tracker_id = $tracker_id;
        return $this;
    }

    public function trackerName(string $tracker_name): self
    {
        $this->tracker_name = $tracker_name;
        return $this;
    }

    /**
     * @param int[] $ids
     */
    public function linkedArtifactIds(array $ids): self
    {
        $this->linked_artifact_ids = $ids;
        return $this;
    }

    public function build(): BaselineArtifact
    {
        return new BaselineArtifact(
            $this->id,
            $this->title,
            $this->description,
            $this->initial_effort,
            $this->status,
            $this->project,
            $this->tracker_id,
            $this->tracker_name,
            $this->linked_artifact_ids
        );
    }
}
