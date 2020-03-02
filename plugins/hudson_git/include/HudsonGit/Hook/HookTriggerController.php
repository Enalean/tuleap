<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use DateTimeImmutable;
use Exception;
use GitRepository;
use Psr\Log\LoggerInterface;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\HudsonGit\Job\CannotCreateJobException;
use Tuleap\HudsonGit\Job\Job;
use Tuleap\HudsonGit\Job\JobManager;

class HookTriggerController
{
    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var JenkinsClient
     */
    private $jenkins_client;
    /**
    * @var JobManager
    */
    private $job_manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JenkinsServerFactory
     */
    private $jenkins_server_factory;

    public function __construct(
        HookDao $dao,
        JenkinsClient $jenkins_client,
        LoggerInterface $logger,
        JobManager $job_manager,
        JenkinsServerFactory $jenkins_server_factory
    ) {
        $this->dao                    = $dao;
        $this->jenkins_client         = $jenkins_client;
        $this->logger                 = $logger;
        $this->job_manager            = $job_manager;
        $this->jenkins_server_factory = $jenkins_server_factory;
    }

    public function trigger(GitRepository $repository, string $commit_reference, DateTimeImmutable $date_time) : void
    {
        $this->triggerRepositoryJenkinsServer($repository, $commit_reference, $date_time);
        $this->triggerProjectJenkinsServers($repository, $commit_reference, $date_time);
    }

    private function triggerRepositoryJenkinsServer(GitRepository $repository, string $commit_reference, DateTimeImmutable $date_time): void
    {
        $date_job = $date_time->getTimestamp();
        $dar = $this->dao->searchById($repository->getId());
        foreach ($dar as $row) {
            $this->logger->debug('Trigger repository jenkins server: ' . $row['jenkins_server_url']);
            $transports = $repository->getAccessURL();
            foreach ($transports as $protocol => $url) {
                try {
                    $response = $this->jenkins_client->pushGitNotifications($row['jenkins_server_url'], $url, $commit_reference);

                    $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                    if (count($response->getJobPaths()) > 0) {
                        $this->logger->debug('Triggered ' . implode(',', $response->getJobPaths()));
                        $this->addHudsonGitJob($repository, implode(',', $response->getJobPaths()), $date_job);
                    }
                } catch (Exception $exception) {
                    $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
                }
            }

            try {
                $this->jenkins_client->pushJenkinsTuleapPluginNotification($row['jenkins_server_url']);
            } catch (UnableToLaunchBuildException $exception) {
                $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
            }
        }
    }

    private function addHudsonGitJob(GitRepository $repository, $job_name, $date_job)
    {
        $job = new Job($repository, $date_job, $job_name);
        try {
            $this->job_manager->create($job);
        } catch (CannotCreateJobException $exception) {
            $this->logger->error('repository #'.$repository->getId().' : '.$exception->getMessage());
        }
    }

    private function triggerProjectJenkinsServers(GitRepository $repository, string $commit_reference, DateTimeImmutable $date_time): void
    {
        $date_job = $date_time->getTimestamp();
        $project  = $repository->getProject();
        foreach ($this->jenkins_server_factory->getJenkinsServerOfProject($project) as $jenkins_server) {
            $this->logger->debug('Trigger project jenkins server:' . $jenkins_server->getServerURL());
            $transports = $repository->getAccessURL();
            foreach ($transports as $protocol => $url) {
                try {
                    $response = $this->jenkins_client->pushGitNotifications($jenkins_server->getServerURL(), $url, $commit_reference);

                    $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                    if (count($response->getJobPaths()) > 0) {
                        $this->logger->debug('Triggered ' . implode(',', $response->getJobPaths()));
                        $this->addProjectJenkinsJobLog(
                            $jenkins_server,
                            $repository,
                            implode(',', $response->getJobPaths()),
                            $date_job
                        );
                    }
                } catch (Exception $exception) {
                    $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
                }
            }

            try {
                $this->jenkins_client->pushJenkinsTuleapPluginNotification($jenkins_server->getServerURL());
            } catch (UnableToLaunchBuildException $exception) {
                $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
            }
        }
    }

    private function addProjectJenkinsJobLog(
        JenkinsServer $jenkins_server,
        GitRepository $repository,
        $job_name,
        $date_job
    ): void {
        $job = new Job($repository, $date_job, $job_name);
        try {
            $this->job_manager->createJobLogForProject($jenkins_server, $job);
        } catch (CannotCreateJobException $exception) {
            $this->logger->error('repository #'.$repository->getId().' : '.$exception->getMessage());
        }
    }
}
