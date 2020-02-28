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

namespace Tuleap\HudsonGit\Job;

use GitRepository;
use GitRepositoryFactory;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;

class JobManager
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
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(JobDao $job_dao, ProjectJobDao $project_job_dao, GitRepositoryFactory $git_repository_factory)
    {
        $this->job_dao                = $job_dao;
        $this->project_job_dao        = $project_job_dao;
        $this->git_repository_factory = $git_repository_factory;
    }

    /**
     * @throws CannotCreateJobException
     */
    public function create(Job $job)
    {
        $id = $this->job_dao->create($job);
        if (! $id) {
            throw new CannotCreateJobException($GLOBALS['Language']->getText('plugin_hudson_git', 'job_error'));
        }
    }

    /**
     * @throws CannotCreateJobException
     */
    public function createJobLogForProject(JenkinsServer $jenkins_server, Job $job)
    {
        if ((int) $jenkins_server->getProject()->getID() !== (int) $job->getRepository()->getProject()->getID()) {
            throw new CannotCreateJobException(
                dgettext("tuleap-hudson_git", "Provided job does not belong to the Jenkins server's project.")
            );
        }

        $this->project_job_dao->create(
            $jenkins_server->getId(),
            $job->getRepository()->getId(),
            $job->getPushDate(),
            $job->getJobUrl()
        );
    }

    public function getJobByRepository(GitRepository $repository)
    {
        $jobs = array();
        foreach ($this->job_dao->searchJobsByRepositoryId($repository->getId()) as $row) {
            $jobs[] = $this->instantiateFromRow($row, $repository);
        }

        return $jobs;
    }

    public function getLastJobLogsByProjectServer(JenkinsServer $jenkins_server): array
    {
        $jobs = [];
        foreach ($this->project_job_dao->searchJobsByJenkinsServer($jenkins_server->getId()) as $row) {
            $repository = $this->git_repository_factory->getRepositoryById((int) $row['repository_id']);
            if ($repository === null) {
                continue;
            }

            $jobs[] = $this->instantiateFromRow($row, $repository);
        }

        return $jobs;
    }

    private function instantiateFromRow(array $row, GitRepository $repository): Job
    {
        return new Job(
            $repository,
            $row['push_date'],
            $row['job_url']
        );
    }
}
