<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;

class ArtifactTestBuilder
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var \Tracker
     */
    private $tracker;
    /**
     * @var string
     */
    private $title = '';
    /**
     * @var \Tracker_ArtifactFactory | null
     */
    private $artifact_factory;

    /**
     * @var \Project|null
     */
    private $project;

    private function __construct(int $id)
    {
        $this->id      = $id;
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(101)
            ->withName("bug")
            ->withColor(TrackerColor::fromName('fiesta-red'))
            ->build();
    }

    public static function anArtifact(int $id): self
    {
        return new self($id);
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function withArtifactFactory(\Tracker_ArtifactFactory $artifact_factory): self
    {
        $this->artifact_factory = $artifact_factory;

        return $this;
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;

        return $this;
    }

    public function inProject(\Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function build(): Artifact
    {
        $artifact = new Artifact(
            $this->id,
            $this->tracker->getId(),
            102,
            1234567890,
            false,
        );

        $artifact->setTracker($this->tracker);
        $artifact->setTitle($this->title);

        if ($this->artifact_factory) {
            $artifact->setArtifactFactory($this->artifact_factory);
        }

        if ($this->project) {
            $artifact->getTracker()->setProject($this->project);
        }

        return $artifact;
    }
}
