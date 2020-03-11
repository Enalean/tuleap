<?php
/**
 * Copyright Enalean (c) 2016-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\HudsonGit\Log;

use GitRepository;

class Log
{
    private $id;
    private $repository;
    private $job_url;
    private $push_date;

    /**
     * @var int|null
     */
    private $status_code;

    public function __construct(GitRepository $repository, int $push_date, string $job_url, ?int $status_code)
    {
        $this->repository  = $repository;
        $this->push_date   = $push_date;
        $this->job_url     = $job_url;
        $this->status_code = $status_code;
    }

    public function getPushDate()
    {
        return $this->push_date;
    }

    public function getFormattedPushDate()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->push_date);
    }

    public function getJobUrl(): string
    {
        return (string) $this->job_url;
    }

    public function getJobUrlList(): array
    {
        if ((string) $this->job_url === '') {
            return [];
        }

        return explode(',', $this->job_url);
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getSha1()
    {
        return $this->id;
    }

    public function getStatusCode(): ?int
    {
        return $this->status_code;
    }
}
