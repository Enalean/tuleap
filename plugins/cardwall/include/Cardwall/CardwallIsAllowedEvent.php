<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Planning_Milestone;
use Tuleap\Event\Dispatchable;

class CardwallIsAllowedEvent implements Dispatchable
{
    public const NAME = "cardwallIsAllowedEvent";

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    private $is_allowed = true;

    public function __construct(Planning_Milestone $milestone)
    {
        $this->milestone = $milestone;
    }

    /**
     * @return Planning_Milestone
     */
    public function getMilestone()
    {
        return $this->milestone;
    }

    public function isCardwallAllowed(): bool
    {
        return $this->is_allowed;
    }

    public function disallowCardwall(): void
    {
        $this->is_allowed = false;
    }
}
