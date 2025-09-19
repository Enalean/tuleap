<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

final class CommitStatusUnknown implements CommitStatus
{
    public const string NAME = 'unknown';

    private $date;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
    }

    /**
     * @return string
     */
    #[\Override]
    public function getStatusName()
    {
        return self::NAME;
    }

    /**
     * @return \DateTimeImmutable
     */
    #[\Override]
    public function getDate()
    {
        return $this->date;
    }
}
