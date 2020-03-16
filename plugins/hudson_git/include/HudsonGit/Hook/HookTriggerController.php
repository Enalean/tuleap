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
use Tuleap\HudsonGit\Log\CannotCreateLogException;
use Tuleap\HudsonGit\Log\Log;
use Tuleap\HudsonGit\Log\LogCreator;

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
    * @var LogCreator
    */
    private $log_creator;

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
        LogCreator $log_creator,
        JenkinsServerFactory $jenkins_server_factory
    ) {
        $this->dao                    = $dao;
        $this->jenkins_client         = $jenkins_client;
        $this->logger                 = $logger;
        $this->log_creator            = $log_creator;
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
            $polling_urls = [];
            foreach ($transports as $protocol => $url) {
                try {
                    $response = $this->jenkins_client->pushGitNotifications($row['jenkins_server_url'], $url, $commit_reference);

                    $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                    if (count($response->getJobPaths()) > 0) {
                        $this->logger->debug('Triggered ' . implode(',', $response->getJobPaths()));
                        $polling_urls = array_merge($polling_urls, $response->getJobPaths());
                    }
                } catch (Exception $exception) {
                    $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
                }
            }

            $status_code = null;
            try {
                $response = $this->jenkins_client->pushJenkinsTuleapPluginNotification($row['jenkins_server_url']);
                $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                $status_code = $response->getStatusCode();
            } catch (UnableToLaunchBuildException $exception) {
                $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
            }

            $this->addHudsonGitLog(
                $repository,
                implode(',', $polling_urls),
                $status_code,
                $date_job
            );
        }
    }

    private function addHudsonGitLog(GitRepository $repository, string $job_name, ?int $status_code, int $date_job): void
    {
        $log = new Log($repository, $date_job, $job_name, $status_code);
        try {
            $this->log_creator->createForRepository($log);
        } catch (CannotCreateLogException $exception) {
            $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
        }
    }

    private function triggerProjectJenkinsServers(GitRepository $repository, string $commit_reference, DateTimeImmutable $date_time): void
    {
        $date_job = $date_time->getTimestamp();
        $project  = $repository->getProject();
        foreach ($this->jenkins_server_factory->getJenkinsServerOfProject($project) as $jenkins_server) {
            $this->logger->debug('Trigger project jenkins server:' . $jenkins_server->getServerURL());
            $transports = $repository->getAccessURL();
            $polling_urls = [];
            foreach ($transports as $protocol => $url) {
                try {
                    $response = $this->jenkins_client->pushGitNotifications($jenkins_server->getServerURL(), $url, $commit_reference);

                    $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                    if (count($response->getJobPaths()) > 0) {
                        $this->logger->debug('Triggered ' . implode(',', $response->getJobPaths()));
                        $polling_urls = array_merge($polling_urls, $response->getJobPaths());
                    }
                } catch (Exception $exception) {
                    $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
                }
            }

            $status_code = null;
            try {
                $response = $this->jenkins_client->pushJenkinsTuleapPluginNotification($jenkins_server->getServerURL());
                $this->logger->debug('repository #' . $repository->getId() . ' : ' . $response->getBody());
                $status_code = $response->getStatusCode();
            } catch (UnableToLaunchBuildException $exception) {
                $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
            }

            $this->addProjectJenkinsJobLog(
                $jenkins_server,
                $repository,
                implode(',', $polling_urls),
                $status_code,
                $date_job
            );
        }
    }

    private function addProjectJenkinsJobLog(
        JenkinsServer $jenkins_server,
        GitRepository $repository,
        string $job_name,
        ?int $status_code,
        $date_job
    ): void {
        $log = new Log($repository, $date_job, $job_name, $status_code);
        try {
            $this->log_creator->createForProject($jenkins_server, $log);
        } catch (CannotCreateLogException $exception) {
            $this->logger->error('repository #' . $repository->getId() . ' : ' . $exception->getMessage());
        }
    }
}
