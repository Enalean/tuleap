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

namespace Tuleap\Timesheeting\Time;

use UserManager;

class TimePresenterBuilder
{
    /**
     * @var DateFormatter
     */
    private $date_formatter;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(DateFormatter $date_formatter, UserManager $user_manager)
    {
        $this->date_formatter = $date_formatter;
        $this->user_manager   = $user_manager;
    }

    /**
     * @return array
     */
    public function buildPresenter(Time $time)
    {
        $user = $this->user_manager->getUserById($time->getUserId());

        return array(
            'day'  => $time->getDay(),
            'user' => $user->getRealName(),
            'time' => $this->date_formatter->formatMinutes($time->getMinutes()),
            'step' => $time->getStep()
        );
    }
}
