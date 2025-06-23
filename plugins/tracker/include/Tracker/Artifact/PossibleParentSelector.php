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
    public \Tuleap\Tracker\Tracker $tracker;
    /**
     * @readonly
     */
    public int $limit = 0;
    /**
     * @readonly
     */
    public int $offset = 0;

    private ?Tracker_Artifact_PaginatedArtifacts $possible_parents = null;
    private bool $display_selector                                 = true;
    private string $parent_label                                   = '';
    private bool $can_create                                       = true;

    public function __construct(\PFUser $user, \Tuleap\Tracker\Tracker $tracker, int $offset, int $limit)
    {
        $this->user    = $user;
        $this->tracker = $tracker;
        $this->offset  = $offset;
        $this->limit   = $limit;
    }

    public function getPossibleParents(): ?Tracker_Artifact_PaginatedArtifacts
    {
        return $this->possible_parents;
    }

    public function isSelectorDisplayed(): bool
    {
        return $this->display_selector;
    }

    public function addPossibleParents(Tracker_Artifact_PaginatedArtifacts $possible_parents): void
    {
        if ($this->possible_parents) {
            $this->possible_parents->addArtifacts($possible_parents->getArtifacts());
            return;
        }
        $this->possible_parents = $possible_parents;
    }

    public function disableSelector(): void
    {
        $this->display_selector = false;
    }

    public function setParentLabel(string $parent_label): void
    {
        $this->parent_label = $parent_label;
    }

    public function getParentLabel(): string
    {
        return $this->parent_label;
    }

    public function canCreate(): bool
    {
        return $this->can_create;
    }

    public function disableCreate(): void
    {
        $this->can_create = false;
    }
}
