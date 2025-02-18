<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use Tuleap\Tracker\Artifact\Artifact;

/**
 * I am a Backlog Item
 */
interface IBacklogItem
{
    public const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';

    public function setInitialEffort(?float $value): void;

    public function getInitialEffort(): ?float;

    public function setStatus(string $status, string $status_semantic): void;

    public function getStatus(): string;

    public function setHasChildren(bool $has_children): void;

    public function id(): int;

    public function title(): string;

    public function getShortType(): string;

    public function color(): string;

    public function hasChildren(): bool;

    public function xRef(): string;

    public function getParent(): ?Artifact;

    public function setParent(Artifact $parent): void;

    public function getArtifact(): Artifact;

    public function isInconsistent(): bool;

    public function getNormalizedStatusLabel(): string;

    public function isOpen(): bool;

    public function getRemainingEffort(): ?float;

    public function setRemainingEffort(?float $value): void;
}
