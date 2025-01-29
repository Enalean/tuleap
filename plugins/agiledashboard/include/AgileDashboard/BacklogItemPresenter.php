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

namespace Tuleap\AgileDashboard;

use Tuleap\AgileDashboard\Milestone\Backlog\BacklogRowPresenter;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Tracker\Artifact\Artifact;

final class BacklogItemPresenter implements IBacklogItem, BacklogRowPresenter
{
    private readonly int $id;
    private readonly string $title;
    private readonly string $type;
    private readonly string $url;
    private readonly string $color;
    private readonly string $short_type;
    private string $status                  = '';
    private string $normalized_status_label = '';
    private ?int $initial_effort            = null;
    private ?float $remaining_effort        = null;
    private ?Artifact $parent               = null;
    private ?bool $has_children             = null;

    public function __construct(
        private readonly Artifact $artifact,
        private readonly string $redirect_to_self,
        private readonly bool $is_inconsistent,
    ) {
        $this->id         = $artifact->getId();
        $this->title      = $artifact->getTitle() ?? '';
        $this->url        = $artifact->getUri();
        $this->type       = $this->artifact->getTracker()->getName();
        $this->color      = $this->artifact->getTracker()->getColor()->getName();
        $this->short_type = $this->artifact->getTracker()->getItemName();
    }

    public function setParent(Artifact $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Artifact
    {
        return $this->parent;
    }

    public function setInitialEffort(?int $value): void
    {
        $this->initial_effort = $value;
    }

    public function getInitialEffort(): ?int
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

    public function url(): string
    {
        return $this->getUrlWithRedirect($this->url);
    }

    public function points(): ?int
    {
        return $this->initial_effort;
    }

    public function parent_title(): ?string
    {
        return $this->parent?->getTitle();
    }

    public function parent_url(): ?string
    {
        if ($this->parent !== null) {
            return $this->getUrlWithRedirect($this->parent->getUri());
        }

        return null;
    }

    public function parent_id(): ?int
    {
        return $this->parent?->getId();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    private function getUrlWithRedirect(string $url): string
    {
        if ($this->redirect_to_self) {
            return $url . '&' . $this->redirect_to_self;
        }
        return $url;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
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

    public function getShortType(): string
    {
        return $this->short_type;
    }
}
