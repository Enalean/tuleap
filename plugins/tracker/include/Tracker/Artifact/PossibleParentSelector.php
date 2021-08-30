<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tracker_Artifact_PaginatedArtifacts;
use Tuleap\Event\Dispatchable;

final class PossibleParentSelector implements Dispatchable
{
    public const NAME = 'trackerArtifactPossibleParentSelector';

    /**
     * @readonly
     */
    public \PFUser $user;
    /**
     * @readonly
     */
    public \Tracker $parent_tracker;

    private ?Tracker_Artifact_PaginatedArtifacts $possible_parents = null;
    private string $label                                          = '';
    private bool $display_selector                                 = true;

    public function __construct(\PFUser $user, \Tracker $parent_tracker)
    {
        $this->user           = $user;
        $this->parent_tracker = $parent_tracker;
    }

    public function getPossibleParents(): ?Tracker_Artifact_PaginatedArtifacts
    {
        return $this->possible_parents;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isSelectorDisplayed(): bool
    {
        return $this->display_selector;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setPossibleParents(Tracker_Artifact_PaginatedArtifacts $possible_parents): void
    {
        $this->possible_parents = $possible_parents;
    }

    public function disableSelector(): void
    {
        $this->display_selector = false;
    }
}
