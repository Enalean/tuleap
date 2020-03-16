<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\Job\ProjectJobDao;

class LogCreator
{
    /**
     * @var JobDao
     */
    private $job_dao;

    /**
     * @var ProjectJobDao
     */
    private $project_job_dao;

    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        JobDao $job_dao,
        ProjectJobDao $project_job_dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->job_dao                = $job_dao;
        $this->project_job_dao        = $project_job_dao;
        $this->transaction_executor   = $transaction_executor;
    }

    /**
     * @throws CannotCreateLogException
     */
    public function createForRepository(Log $log)
    {
        $this->checkLogCanBeCreated($log);

        $this->transaction_executor->execute(function () use ($log) {
            $id = $this->job_dao->create($log);
            if (!$id) {
                throw new CannotCreateLogException($GLOBALS['Language']->getText('plugin_hudson_git', 'job_error'));
            }
            if (count($log->getJobUrlList()) > 0) {
                $this->job_dao->logTriggeredJobs(
                    $id,
                    $log->getJobUrl()
                );
            }

            $status_code = $log->getStatusCode();
            if ($status_code !== null) {
                $this->job_dao->logBranchSource($id, $status_code);
            }
        });
    }

    /**
     * @throws CannotCreateLogException
     */
    public function createForProject(JenkinsServer $jenkins_server, Log $log)
    {
        if ((int) $jenkins_server->getProject()->getID() !== (int) $log->getRepository()->getProject()->getID()) {
            throw new CannotCreateLogException(
                dgettext("tuleap-hudson_git", "Provided job does not belong to the Jenkins server's project.")
            );
        }
        $this->checkLogCanBeCreated($log);

        $this->transaction_executor->execute(function () use ($jenkins_server, $log) {
            $log_id = $this->project_job_dao->create(
                $jenkins_server->getId(),
                $log->getRepository()->getId(),
                $log->getPushDate()
            );
            if (count($log->getJobUrlList()) > 0) {
                $this->project_job_dao->logTriggeredJobs(
                    $log_id,
                    $log->getJobUrl()
                );
            }

            $status_code = $log->getStatusCode();
            if ($status_code !== null) {
                $this->project_job_dao->logBranchSource($log_id, $status_code);
            }
        });
    }

    /**
     * @throws CannotCreateLogException
     */
    private function checkLogCanBeCreated(Log $log): void
    {
        if ($log->getStatusCode() === null && count($log->getJobUrlList()) === 0) {
            throw new CannotCreateLogException(
                dgettext("tuleap-hudson_git", "Nothing has been triggered for this push.")
            );
        }
    }
}
