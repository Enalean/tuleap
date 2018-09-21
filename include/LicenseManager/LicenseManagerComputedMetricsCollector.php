<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Enalean\LicenseManager;

use Tuleap\Instrument\Prometheus\Prometheus;

class LicenseManagerComputedMetricsCollector
{
    /**
     * @var Prometheus
     */
    private $prometheus;
    /**
     * @var int
     */
    private $max_users;

    public function __construct(Prometheus $prometheus, $max_users)
    {
        $this->prometheus = $prometheus;
        $this->max_users  = $max_users;
    }

    public function collect()
    {
        $this->prometheus->gaugeSet(
            'licence_max_users',
            'Maximum number of users allowed on the instance',
            $this->max_users
        );
    }
}
