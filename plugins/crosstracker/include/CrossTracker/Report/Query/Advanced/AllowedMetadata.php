<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

final class AllowedMetadata
{
    public const TITLE            = '@title';
    public const DESCRIPTION      = '@description';
    public const STATUS           = '@status';
    public const SUBMITTED_ON     = '@submitted_on';
    public const LAST_UPDATE_DATE = '@last_update_date';
    public const SUBMITTED_BY     = '@submitted_by';
    public const LAST_UPDATE_BY   = '@last_update_by';
    public const ASSIGNED_TO      = '@assigned_to';

    public const NAMES = [
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY,
        self::ASSIGNED_TO,
    ];

    public const DATES = [
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
    ];

    public const USERS = [
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY,
        self::ASSIGNED_TO,
    ];
}
