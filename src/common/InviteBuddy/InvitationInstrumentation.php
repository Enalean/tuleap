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

namespace Tuleap\InviteBuddy;

use Tuleap\Instrument\Prometheus\Prometheus;

class InvitationInstrumentation
{
    private const METRIC_NAME = 'user_invitations_total';
    private const HELP        = 'Total number of invitations sent by users';

    /**
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(Prometheus $prometheus)
    {
        $this->prometheus = $prometheus;
    }

    public function increment(): void
    {
        $this->prometheus->increment(self::METRIC_NAME, self::HELP);
    }
}
