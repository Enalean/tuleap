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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\HudsonGit\Job\ProjectJobDao;

class JenkinsServerDeleter
{
    /**
     * @var JenkinsServerDao
     */
    private $jenkins_server_dao;

    /**
     * @var ProjectJobDao
     */
    private $project_job_dao;

    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        JenkinsServerDao $jenkins_server_dao,
        ProjectJobDao $project_job_dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->jenkins_server_dao   = $jenkins_server_dao;
        $this->project_job_dao      = $project_job_dao;
        $this->transaction_executor = $transaction_executor;
    }

    public function deleteServer(JenkinsServer $jenkins_server): void
    {
        $this->transaction_executor->execute(function () use ($jenkins_server) {
            $this->project_job_dao->deleteLogsOfServer($jenkins_server->getId());
            $this->jenkins_server_dao->deleteJenkinsServer($jenkins_server->getId());
        });
    }
}
