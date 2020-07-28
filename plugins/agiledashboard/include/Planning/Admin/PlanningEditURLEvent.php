<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\Admin;

use Planning;
use Tuleap\Event\Dispatchable;

final class PlanningEditURLEvent implements Dispatchable
{
    public const NAME = 'planningEditURLEvent';

    /**
     * @var Planning
     */
    private $planning;

    /**
     * @var Planning|null
     */
    private $root_planning;

    /**
     * @var string
     */
    private $edit_url;

    public function __construct(Planning $planning, ?Planning $root_planning)
    {
        $this->planning      = $planning;
        $this->root_planning = $root_planning;

        $this->edit_url = $this->buildDefaultURL();
    }

    private function buildDefaultURL(): string
    {
        return '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $this->planning->getGroupId(),
            'planning_id' => $this->planning->getId(),
            'action' => 'edit',
        ]);
    }

    public function getEditUrl(): string
    {
        return $this->edit_url;
    }

    public function setEditUrl(string $edit_url): void
    {
        $this->edit_url = $edit_url;
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getRootPlanning(): ?Planning
    {
        return $this->root_planning;
    }
}
