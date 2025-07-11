<?php
/**
 * Copyright Enalean (c) 2013-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use Tuleap\Tracker\Artifact\Artifact;

class BacklogItem implements IBacklogItem
{
    private readonly int $id;
    private readonly string $title;
    private readonly string $type;
    private readonly string $short_type;
    private readonly string $color;
    private string $status                  = '';
    private string $normalized_status_label = '';
    private ?float $initial_effort          = null;
    private ?float $remaining_effort        = null;
    private ?Artifact $parent               = null;
    private ?bool $has_children             = null;

    public function __construct(
        private readonly Artifact $artifact,
        private readonly bool $is_inconsistent,
    ) {
        $this->id         = $artifact->getId();
        $this->title      = $artifact->getTitle() ?? '';
        $tracker          = $this->artifact->getTracker();
        $this->color      = $tracker->getColor()->value;
        $this->type       = $tracker->getName();
        $this->short_type = $tracker->getItemName();
    }

    public function setParent(Artifact $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Artifact
    {
        return $this->parent;
    }

    public function setInitialEffort(?float $value): void
    {
        $this->initial_effort = $value;
    }

    public function getInitialEffort(): ?float
    {
        return $this->initial_effort;
    }

    public function setStatus(string $status, string $status_semantic): void
    {
        $this->status                  = $status;
        $this->normalized_status_label = $status_semantic;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function getShortType(): string
    {
        return $this->short_type;
    }

    public function points(): ?float
    {
        return $this->initial_effort;
    }

    public function parent_title(): ?string //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->parent?->getTitle();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function setHasChildren(bool $has_children): void
    {
        $this->has_children = $has_children;
    }

    public function hasChildren(): bool
    {
        if ($this->has_children === null) {
            return $this->artifact->hasChildren();
        }
        return $this->has_children;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function xRef(): string
    {
        return $this->artifact->getXRef();
    }

    public function isInconsistent(): bool
    {
        return $this->is_inconsistent;
    }

    public function getNormalizedStatusLabel(): string
    {
        return $this->normalized_status_label;
    }

    public function isOpen(): bool
    {
        return $this->artifact->isOpen();
    }

    public function getRemainingEffort(): ?float
    {
        return $this->remaining_effort;
    }

    public function setRemainingEffort(?float $value): void
    {
        $this->remaining_effort = $value;
    }
}
