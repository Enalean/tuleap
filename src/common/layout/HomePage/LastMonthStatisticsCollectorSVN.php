<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\layout\HomePage;

use Tuleap\Event\Dispatchable;

class LastMonthStatisticsCollectorSVN implements Dispatchable
{
    public const NAME = 'lastMonthStatisticsCollectorSVN';
    /**
     * @var int
     */
    public $svn_commits = 0;
    /**
     * @var int
     */
    private $timestamp;

    public function __construct(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp() : int
    {
        return $this->timestamp;
    }

    public function getSVNPluginCommitsCount() : int
    {
        return $this->svn_commits;
    }

    public function setSvnCommits(int $svn_commits)
    {
        $this->svn_commits = $svn_commits;
    }
}
