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

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;

final class ArtifactTestBuilder
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var \Tracker
     */
    private $tracker;
    private string $title       = '';
    private string $description = '';
    /**
     * @var \Tracker_Artifact_Changeset[]
     */
    private array $changesets                                = [];
    private \Tracker_Artifact_Changeset|null $last_changeset = null;
    /**
     * @var \Tracker_ArtifactFactory | null
     */
    private $artifact_factory;

    /**
     * @var array<int, bool>
     */
    private array $user_can_view = [];

    /**
     * @var \Project|null
     */
    private $project;
    private int $submission_timestamp  = 1234567890;
    private ?PFUser $submitted_by_user = null;
    private ?Artifact $parent          = null;
    private bool $has_parent           = false;
    private bool|null $is_open         = null;
    private string|null $status        = null;
    /**
     * @var Artifact[]|null
     */
    private ?array $ancestors = null;

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

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function submittedBy(PFUser $user): self
    {
        $this->submitted_by_user = $user;

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

    public function withSubmissionTimestamp(int $submission_timestamp): self
    {
        $this->submission_timestamp = $submission_timestamp;
        return $this;
    }

    /** @no-named-arguments */
    public function withChangesets(\Tracker_Artifact_Changeset $last_changeset, \Tracker_Artifact_Changeset ...$previous_changesets): self
    {
        $this->changesets     = [...$previous_changesets, $last_changeset];
        $this->last_changeset = $last_changeset;

        return $this;
    }

    public function userCanView(PFUser $user): self
    {
        $this->user_can_view[$user->getId()] = true;
        return $this;
    }

    public function userCannotView(PFUser $user): self
    {
        $this->user_can_view[$user->getId()] = false;
        return $this;
    }

    public function withParent(?Artifact $artifact): self
    {
        $this->parent     = $artifact;
        $this->has_parent = true;
        return $this;
    }

    public function isOpen(bool $is_open): self
    {
        $this->is_open = $is_open;

        return $this;
    }

    public function withStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param Artifact[] $ancestors
     */
    public function withAncestors(array $ancestors): self
    {
        $this->ancestors = $ancestors;

        return $this;
    }

    public function build(): Artifact
    {
        $artifact = new class (
            $this->id,
            $this->tracker->getId(),
            $this->submission_timestamp,
            $this->user_can_view,
        ) extends Artifact {
            /**
             * @param array<int, bool> $user_can_view
             */
            public function __construct(
                int $id,
                int $tracker_id,
                int $submitted_on,
                private readonly array $user_can_view,
            ) {
                parent::__construct($id, $tracker_id, 102, $submitted_on, false);
            }

            public function userCanView(?PFUser $user = null): bool
            {
                if ($user && isset($this->user_can_view[(int) $user->getId()])) {
                    return $this->user_can_view[(int) $user->getId()];
                }
                return parent::userCanView($user);
            }
        };

        $artifact->setTracker($this->tracker);
        $artifact->setTitle($this->title);
        $artifact->setDescription($this->description);

        if ($this->submitted_by_user) {
            $artifact->setSubmittedByUser($this->submitted_by_user);
        }

        if ($this->artifact_factory) {
            $artifact->setArtifactFactory($this->artifact_factory);
        }

        if ($this->project) {
            $artifact->getTracker()->setProject($this->project);
        }

        if ($this->changesets) {
            $artifact->setChangesets($this->changesets);
        }

        if ($this->last_changeset) {
            $artifact->setLastChangeset($this->last_changeset);
        }
        if ($this->has_parent) {
            $artifact->setParent($this->parent);
        }
        if ($this->is_open !== null) {
            $artifact->setIsOpen($this->is_open);
        }
        if ($this->ancestors !== null) {
            $artifact->setAllAncestors($this->ancestors);
        }
        if ($this->status !== null) {
            $artifact->setStatus($this->status);
        }

        return $artifact;
    }
}
