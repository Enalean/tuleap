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

namespace Tuleap\HudsonSvn;

use Tuleap\HudsonSvn\Job\Job;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVNCore\Repository;
use Tuleap\HudsonSvn\Job\Dao;
use Tuleap\HudsonSvn\Job\Factory;
use TemplateRenderer;
use Project;

class ContinuousIntegrationCollector
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(
        TemplateRenderer $renderer,
        RepositoryManager $repository_manager,
        Dao $dao,
        Factory $factory,
    ) {
        $this->renderer           = $renderer;
        $this->repository_manager = $repository_manager;
        $this->dao                = $dao;
        $this->factory            = $factory;
    }

    public function collect(Project $project, ?int $job_id)
    {
        $job_ids      = $this->getJobIdsThatTriggerCommit($project);
        $job          = $this->getJob($job_id);
        $repositories = $this->getRepositories($project, $job);

        if (count($repositories) > 0) {
            $html_form = $this->renderer->renderToString(
                "hudson_svn_form",
                new FormPresenter(
                    $repositories,
                    $this->doesJobTriggerCommit($job_ids, $job_id),
                    $this->getPath($job)
                )
            );

            return [
                'service'   => "",
                'title'     => dgettext('tuleap-hudson_svn', 'Subversion multi repositories trigger'),
                'used'      => $job_ids,
                'add_form'  => $html_form,
                'edit_form' => $html_form,
            ];
        }
    }

    private function getPath($job)
    {
        if ($job !== null) {
            return $job->getPath();
        }

        return '';
    }

    private function getJob(?int $job_id): ?Job
    {
        if ($job_id !== null) {
            return $this->factory->getJobById($job_id);
        }

        return null;
    }

    private function doesJobTriggerCommit(array $job_ids, $job_id)
    {
        return $job_id !== null && array_key_exists($job_id, $job_ids);
    }

    private function getJobIdsThatTriggerCommit(Project $project)
    {
        $used = [];

        foreach ($this->getJobIds($project) as $row) {
            $used[$row["id"]] = true;
        }

        return $used;
    }

    private function getRepositories(Project $project, $job)
    {
        $repositories_presenter = [];

        foreach ($this->repository_manager->getRepositoriesInProject($project) as $repository) {
            $repositories_presenter[] = [
                'id'          => $repository->getId(),
                'name'        => $repository->getName(),
                'is_selected' => $this->isRepositorySelected($repository, $job),
            ];
        }

        return $repositories_presenter;
    }

    private function isRepositorySelected(Repository $repository, $job)
    {
        if ($job === null) {
            return false;
        }

        return $job->getRepositoryId() === $repository->getId();
    }

    private function getJobIds(Project $project)
    {
        return $this->dao->getJobIds($project);
    }
}
