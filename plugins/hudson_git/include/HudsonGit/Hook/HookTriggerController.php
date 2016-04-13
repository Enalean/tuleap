<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use Logger;
use GitRepository;
use Jenkins_Client;
use Exception;
use Tuleap\HudsonGit\Job\Job;
use Tuleap\HudsonGit\Job\JobManager;

class HookTriggerController
{

    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var Jenkins_Client
     */
    private $jenkins_client;
    /**
    * @var JobManager
    */
    private $job_manager;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(HookDao $dao, Jenkins_Client $jenkins_client, Logger $logger, JobManager $job_manager)
    {
        $this->dao            = $dao;
        $this->jenkins_client = $jenkins_client;
        $this->logger         = $logger;
        $this->job_manager    = $job_manager;
    }

    public function trigger(GitRepository $repository)
    {
        $date_job = $_SERVER['REQUEST_TIME'];
        $dar = $this->dao->getById($repository->getId());
        foreach ($dar as $row) {
            try {
                $transports = $repository->getAccessURL();
                foreach ($transports as $protocol => $url) {
                    $response = $this->jenkins_client->pushGitNotifications($row['jenkins_server_url'], $url);

                    $this->logger->debug('repository #'.$repository->getId().' : '.$response->getBody());
                    if (count($response->getJobPaths()) > 0) {
                        $this->logger->debug('Triggered ' . implode(',', $response->getJobPaths()));
                        $this->addHudsongitJob($repository, implode(',', $response->getJobPaths()), $date_job);
                    }
                }
            } catch (Exception $exception) {
                $this->logger->error('repository #'.$repository->getId().' : '.$exception->getMessage());
            }
        }
    }

    private function addHudsongitJob(GitRepository $repository, $job_name, $date_job)
    {
        $job = new Job($repository, $date_job, $job_name);
        try {
            $this->job_manager->create($job);
        } catch (CannotCreateJobException $exception) {
            $this->logger->error('repository #'.$repository->getId().' : '.$exception->getMessage());
        }
    }
}
