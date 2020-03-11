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

namespace Tuleap\HudsonGit\Git\Administration;

use Tuleap\HudsonGit\Log\Log;

class JenkinsServerLogsPresenter
{
    /**
     * @var string
     */
    public $repository_name;

    /**
     * @var string
     */
    public $push_date;

    /**
     * @var String[]
     */
    public $triggered_jobs;

    /**
     * @var int|null
     */
    public $status_code;

    public function __construct(
        string $repository_name,
        string $formatted_push_date,
        array $triggered_jobs,
        ?int $status_code
    ) {
        $this->repository_name = $repository_name;
        $this->push_date       = $formatted_push_date;
        $this->triggered_jobs  = $triggered_jobs;
        $this->status_code     = $status_code;
    }

    public static function buildFromLog(Log $log): self
    {
        return new self(
            (string) $log->getRepository()->getName(),
            (string) $log->getFormattedPushDate(),
            $log->getJobUrlList(),
            $log->getStatusCode()
        );
    }
}
