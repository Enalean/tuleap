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

use Override;
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
    private ?float $initial_effort          = null;
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
        $tracker          = $this->artifact->getTracker();
        $this->type       = $tracker->getName();
        $this->color      = $tracker->getColor()->value;
        $this->short_type = $tracker->getItemName();
    }

    #[Override]
    public function setParent(Artifact $parent): void
    {
        $this->parent = $parent;
    }

    #[Override]
    public function getParent(): ?Artifact
    {
        return $this->parent;
    }

    #[Override]
    public function setInitialEffort(?float $value): void
    {
        $this->initial_effort = $value;
    }

    #[Override]
    public function getInitialEffort(): ?float
    {
        return $this->initial_effort;
    }

    #[Override]
    public function setStatus(string $status, string $status_semantic): void
    {
        $this->status                  = $status;
        $this->normalized_status_label = $status_semantic;
    }

    #[Override]
    public function id(): int
    {
        return $this->id;
    }

    #[Override]
    public function title(): string
    {
        return $this->title;
    }

    public function type(): string
    {
        return $this->type;
    }

    #[Override]
    public function url(): string
    {
        return $this->getUrlWithRedirect($this->url);
    }

    #[Override]
    public function points(): ?float
    {
        return $this->initial_effort;
    }

    #[Override]
    public function parent_title(): ?string //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->parent?->getTitle();
    }

    #[Override]
    public function parent_url(): ?string //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->parent !== null) {
            return $this->getUrlWithRedirect($this->parent->getUri());
        }

        return null;
    }

    public function parent_id(): ?int //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->parent?->getId();
    }

    #[Override]
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

    #[Override]
    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    #[Override]
    public function color(): string
    {
        return $this->color;
    }

    #[Override]
    public function setHasChildren(bool $has_children): void
    {
        $this->has_children = $has_children;
    }

    #[Override]
    public function hasChildren(): bool
    {
        if ($this->has_children === null) {
            return $this->artifact->hasChildren();
        }
        return $this->has_children;
    }

    #[Override]
    public function xRef(): string
    {
        return $this->artifact->getXRef();
    }

    #[Override]
    public function isInconsistent(): bool
    {
        return $this->is_inconsistent;
    }

    #[Override]
    public function getNormalizedStatusLabel(): string
    {
        return $this->normalized_status_label;
    }

    #[Override]
    public function isOpen(): bool
    {
        return $this->artifact->isOpen();
    }

    #[Override]
    public function getRemainingEffort(): ?float
    {
        return $this->remaining_effort;
    }

    #[Override]
    public function setRemainingEffort(?float $value): void
    {
        $this->remaining_effort = $value;
    }

    #[Override]
    public function getShortType(): string
    {
        return $this->short_type;
    }
}
