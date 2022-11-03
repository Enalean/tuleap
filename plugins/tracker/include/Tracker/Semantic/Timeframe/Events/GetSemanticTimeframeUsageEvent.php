<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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


namespace Tuleap\Tracker\Semantic\Timeframe\Events;

use Tuleap\Event\Dispatchable;

class GetSemanticTimeframeUsageEvent implements Dispatchable
{
    public const NAME = 'getSemanticTimeframeUsageEvent';

    /**
     * @var string[]
     */
    private $usage_locations = [];

    public function addUsageLocation(string $location): void
    {
        $this->usage_locations[] = $location;
    }

    public function getSemanticUsage(): string
    {
        if (empty($this->usage_locations)) {
            return dgettext(
                'tuleap-tracker',
                'This semantic is unused at the moment.'
            );
        }

        $locations = implode(', ', $this->usage_locations);

        return sprintf(
            dgettext(
                'tuleap-tracker',
                'The timeframe semantic summarizes the duration of artifact in %s.'
            ),
            $locations
        );
    }
}
