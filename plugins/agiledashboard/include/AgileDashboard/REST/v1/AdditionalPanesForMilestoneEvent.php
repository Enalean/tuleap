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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1;

use Planning_Milestone;
use Tuleap\Event\Dispatchable;

final class AdditionalPanesForMilestoneEvent implements Dispatchable
{
    public const NAME = 'additionalPanesForMilestoneEvent';

    /**
     * @var PaneInfoRepresentation[]
     */
    private $pane_info_representations = [];
    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_Milestone $milestone)
    {
        $this->milestone = $milestone;
    }

    public function getMilestone(): Planning_Milestone
    {
        return $this->milestone;
    }

    public function add(PaneInfoRepresentation $pane_info_representation): void
    {
        $this->pane_info_representations[] = $pane_info_representation;
    }

    public function getPaneInfoRepresentations(): array
    {
        return $this->pane_info_representations;
    }
}
