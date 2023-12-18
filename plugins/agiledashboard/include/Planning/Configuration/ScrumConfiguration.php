<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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


namespace Tuleap\AgileDashboard\Planning\Configuration;

use Planning;
use Tuleap\AgileDashboard\Planning\RetrievePlannings;

/**
 * @psalm-immutable
 */
final class ScrumConfiguration
{
    /**
     * @var Planning[]
     */
    private array $plannings;
    /**
     * @var Planning[]
     */
    private array $last_plannings;

    /**
     * @param Planning[] $plannings
     * @param Planning[]      $last_plannings
     */
    private function __construct(array $plannings, array $last_plannings)
    {
        $this->plannings      = $plannings;
        $this->last_plannings = $last_plannings;
    }

    /**
     * @return Planning[]
     */
    public function getPlannings(): array
    {
        return $this->plannings;
    }

    /**
     * @return Planning[]
     */
    public function getLastPlannings(): array
    {
        return $this->last_plannings;
    }

    public static function fromProjectId(RetrievePlannings $retriever, int $project_id, \PFUser $user): self
    {
        $plannings      = $retriever->getNonLastLevelPlannings($user, $project_id);
        $last_plannings = $retriever->getLastLevelPlannings($user, $project_id);

        return new self($plannings, $last_plannings);
    }
}
