<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use Tuleap\HudsonSvn\BuildParams;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Commit\CommitInfo;
use Jenkins_Client;
use Jenkins_ClientUnableToLaunchBuildException;
use Psr\Log\LoggerInterface;

class Launcher
{
    public const ROOT_DIRECTORY = '/';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Factory
     */
    private $factory;

    /** @var Jenkins_Client  */
    private $ci_client;

    /** @var BuildParams  */
    private $build_params;

    private $launched_jobs = [];

    public function __construct(Factory $factory, LoggerInterface $logger, Jenkins_Client $ci_client, BuildParams $build_params)
    {
        $this->factory      = $factory;
        $this->logger       = $logger;
        $this->ci_client    = $ci_client;
        $this->build_params = $build_params;
    }

    public function launch(Repository $repository, CommitInfo $commit_info)
    {
        if (! $repository->getProject()->usesService('hudson')) {
            return;
        }

        $jobs = $this->getJobsForRepository($repository);

        foreach ($jobs as $job) {
            $this->logger->debug("Processing job #" . $job->getId());

            if ($this->doesCommitTriggerjob($commit_info, $job)) {
                $job_id = $job->getId();

                if ($this->isJobAlreadyLaunched($job)) {
                    $this->logger->info("Job #$job_id not launched because another job with same URL and parameters already triggered by another job. Skipping.");

                    continue;
                }

                $this->logger->info("Launching job #$job_id triggered by repository " . $repository->getFullName() . " with the url " . $job->getUrl());
                try {
                    $this->ci_client->setToken($job->getToken());
                    $this->ci_client->launchJobBuild(
                        $job->getUrl(),
                        $this->build_params->getAdditionalSvnParameters($repository, $commit_info)
                    );

                    $this->launched_jobs[] = $job->getUrl();
                } catch (Jenkins_ClientUnableToLaunchBuildException $exception) {
                    $this->logger->error("Launching job #$job_id triggered by repository " . $repository->getFullName() . " with the url " . $job->getUrl() . " returns an error " . $exception->getMessage());
                }

                continue;
            }
        }
    }

    private function isJobAlreadyLaunched(Job $job)
    {
        return in_array($job->getUrl(), $this->launched_jobs);
    }

    private function doesCommitTriggerjob(CommitInfo $commit_info, Job $job)
    {
        $job_paths                       = explode(PHP_EOL, $job->getPath());
        $well_formed_changed_directories = $this->getWellFormedChangedDirectories($commit_info);

        foreach ($job_paths as $path) {
            $regexp = $this->getRegExpFromPath($path);

            foreach ($well_formed_changed_directories as $directory) {
                if (preg_match($regexp, $directory)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getRegExpFromPath($path)
    {
        $path = preg_quote($path);
        $path = str_replace('\*', '[^/]+', $path);
        $path = '#^' . $path . '#';

        return $path;
    }

    /**
     * @return array
     */
    private function getWellFormedChangedDirectories(CommitInfo $commit_info)
    {
        $well_formed_directories = [];
        foreach ($commit_info->getChangedDirectories() as $changed_directory) {
            if ($changed_directory !== self::ROOT_DIRECTORY) {
                $changed_directory = self::ROOT_DIRECTORY . $changed_directory;
            }

            $well_formed_directories[] = $changed_directory;
        }

        return $well_formed_directories;
    }

    private function getJobsForRepository(Repository $repository)
    {
        return $this->factory->getJobsByRepository($repository);
    }
}
