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
 *
 */

declare(strict_types=1);

namespace Tuleap\CreateTestEnv\ActivitiesAnalytics;

final class DailyUsageRowPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $real_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $login;
    /**
     * @var string
     * @psalm-readonly
     */
    public $email;
    /**
     * @var int
     * @psalm-readonly
     */
    public $actions;
    /**
     * @var int
     * @psalm-readonly
     */
    public $connexions;
    /**
     * @var string
     * @psalm-readonly
     */
    public $last_seen;
    /**
     * @var string
     * @psalm-readonly
     */
    public $elapsed_time;

    public function __construct(string $real_name, string $login, string $email, int $actions, int $connexions, \DateTimeImmutable $last_seen, \DateInterval $elapsed_time)
    {
        $this->real_name = $real_name;
        $this->login = $login;
        $this->email = $email;
        $this->actions = $actions;
        $this->connexions = $connexions;
        $this->last_seen = $last_seen->format('Y-m-d H:i');
        if ($elapsed_time->days > 0) {
            $this->elapsed_time = $elapsed_time->format('%a days %H:%I');
        } else {
            $this->elapsed_time = $elapsed_time->format('%H:%I');
        }
    }
}
