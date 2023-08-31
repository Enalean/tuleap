<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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


namespace Tuleap\Tracker\Semantic\Progress\Events;

use Tuleap\Event\Dispatchable;

class GetSemanticProgressUsageEvent implements Dispatchable
{
    public const NAME = 'getSemanticProgressUsageEvent';

    public function __construct(public readonly \Tracker $tracker)
    {
    }

    /**
     * @var array
     */
    private $usage_locations = [];
    /**
     * @var array
     */
    private $future_usage_location = [];

    public function addUsageLocation(string $location): void
    {
        $this->usage_locations[] = $location;
    }

    public function addFutureUsageLocation(string $future_usage_location): void
    {
        $this->future_usage_location[] = $future_usage_location;
    }

    public function getSemanticUsage(): string
    {
        if (empty($this->usage_locations) && empty($this->future_usage_location)) {
            return dgettext(
                'tuleap-tracker',
                'This semantic is unused at the moment.'
            );
        }

        $locations       = implode(', ', $this->usage_locations);
        $futur_locations = implode(', ', $this->future_usage_location);

        if (! empty($this->usage_locations) && empty($this->future_usage_location)) {
            return sprintf(
                dgettext(
                    'tuleap-tracker',
                    'This semantic is only used in %s at the moment.'
                ),
                $locations
            );
        }

        if (! empty($this->future_usage_location) && empty($this->usage_locations)) {
            return sprintf(
                dgettext(
                    'tuleap-tracker',
                    'This semantic is unused at the moment. In longer-term, we plan to use it in %s.'
                ),
                $futur_locations
            );
        }

        return sprintf(
            dgettext(
                'tuleap-tracker',
                'This semantic is only used in %s at the moment. In longer-term, we plan to use it in %s as well.'
            ),
            $locations,
            $futur_locations
        );
    }
}
