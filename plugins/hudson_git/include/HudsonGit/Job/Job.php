<?php
/**
 * Copyright Enalean (c) 2016-2018. All rights reserved.
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

namespace Tuleap\HudsonGit\Job;

use GitRepository;

class Job
{
    private $id;
    private $repository;
    private $job_url;
    private $push_date;

    public function __construct(GitRepository $repository, $push_date, $job_url)
    {
        $this->repository = $repository;
        $this->push_date  = $push_date;
        $this->job_url    = $job_url;
    }

    public function getPushDate()
    {
        return $this->push_date;
    }

    public function getFormattedPushDate()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->push_date);
    }

    public function getJobUrl()
    {
        return (string) $this->job_url;
    }

    public function getJobUrlList()
    {
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
}
