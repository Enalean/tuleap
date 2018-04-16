<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

class AllowedMetadata
{
    const TITLE            = '@title';
    const DESCRIPTION      = '@description';
    const STATUS           = '@status';
    const SUBMITTED_ON     = '@submitted_on';
    const LAST_UPDATE_DATE = '@last_update_date';
    const SUBMITTED_BY     = '@submitted_by';
    const LAST_UPDATE_BY   = '@last_update_by';

    const NAMES = [
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY
    ];

    const DATES = [
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE
    ];

    const USERS = [
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY
    ];
}
