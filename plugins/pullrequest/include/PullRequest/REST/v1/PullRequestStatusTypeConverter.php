<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;

final class PullRequestStatusTypeConverter
{
    private const UPDATE  = 'update';
    private const REBASE  = 'rebase';
    private const MERGE   = 'merge';
    private const ABANDON = 'abandon';
    private const REOPEN  = 'reopen';

    public static function fromIntStatusToStringStatus(int $type_acronym): string
    {
        $status_name = [
            TimelineGlobalEvent::UPDATE  => self::UPDATE,
            TimelineGlobalEvent::REBASE  => self::REBASE,
            TimelineGlobalEvent::MERGE   => self::MERGE,
            TimelineGlobalEvent::ABANDON => self::ABANDON,
            TimelineGlobalEvent::REOPEN  => self::REOPEN,
        ];

        return $status_name[$type_acronym];
    }
}
