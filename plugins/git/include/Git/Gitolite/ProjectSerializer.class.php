<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\PathJoinUtil;

class Git_Gitolite_ProjectSerializer
{

    public const OBJECT_SIZE_LIMIT = 52428800;

    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    /**
     * @var Git_Gitolite_ConfigPermissionsSerializer
     */
    private $permissions_serializer;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @var BigObjectAuthorizationManager
     */
    private $big_object_authorization_manager;

    /**
     * @var VersionDetector
     */
    private $version_detector;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        GitRepositoryFactory $repository_factory,
        Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
        Git_GitRepositoryUrlManager $url_manager,
        BigObjectAuthorizationManager $big_object_authorization_manager,
        VersionDetector $version_detector
    ) {
        $this->logger                           = $logger;
        $this->repository_factory               = $repository_factory;
        $this->permissions_serializer           = $permissions_serializer;
        $this->url_manager                      = $url_manager;
        $this->big_object_authorization_manager = $big_object_authorization_manager;
        $this->version_detector                 = $version_detector;
    }

    /**
     * Save on filesystem all permission configuration for a project
     *
     */
    public function dumpProjectRepoConf(Project $project)
    {
        $this->logger->debug("Dumping project repo conf for: " . $project->getUnixName());

        $project_config = '';
        foreach ($this->repository_factory->getAllRepositoriesOfProject($project) as $repository) {
            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . "...");
            $project_config .= $this->fetchReposConfig($project, $repository);
            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . ": done");
        }

        return $project_config;
    }

    public function dumpPartialProjectRepoConf(Project $project, array $repositories)
    {
        $this->logger->debug("Dumping partial project repo conf for: " . $project->getUnixName());
        $project_config = '';
        foreach ($repositories as $repository) {
            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . "...");
            $project_config .= $this->fetchReposConfig($project, $repository);
            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . ": done");
        }

        return $project_config;
    }

    public function dumpPartialSuspendedProjectRepositoriesConfiguration(Project $project, array $repositories)
    {
        $this->logger->debug("Dumping partial suspended project repo conf for: " . $project->getUnixName());
        $project_config = '';
        foreach ($repositories as $repository) {
            $this->logger->debug("Fetching disabled repo configuration: " . $repository->getName() . "...");
            $project_config .= $this->fetchSuspendedRepositoryConfiguration($project, $repository);
            $this->logger->debug("Fetching disabled repo configuration: " . $repository->getName() . ": done");
        }

        return $project_config;
    }

    public function dumpSuspendedProjectRepositoriesConfiguration(Project $project)
    {
        $this->logger->debug("Dumping suspended project repo conf for: " . $project->getUnixName());

        $project_config = '';
        foreach ($this->repository_factory->getAllRepositoriesOfProject($project) as $repository) {
            $this->logger->debug("Fetching disabled repo configuration: " . $repository->getName() . "...");
            $project_config .= $this->fetchSuspendedRepositoryConfiguration($project, $repository);
            $this->logger->debug("Fetching disabled repo configuration: " . $repository->getName() . ": done");
        }

        return $project_config;
    }

    private function fetchSuspendedRepositoryConfiguration(Project $project, GitRepository $repository)
    {
        $repo_full_name = $this->repoFullName($repository, $project->getUnixName());
        $repo_config  = 'repo ' . $repo_full_name . PHP_EOL;
        $repo_config .= $this->permissions_serializer->denyAccessForRepository();

        return $repo_config . PHP_EOL;
    }

    protected function fetchReposConfig(Project $project, GitRepository $repository)
    {
        $repo_full_name   = $this->repoFullName($repository, $project->getUnixName());
        $repo_config  = 'repo ' . $repo_full_name . PHP_EOL;
        $repo_config .= $this->fetchMailHookConfig($project, $repository);
        $repo_config .= $this->permissions_serializer->getForRepository($repository);
        $repo_config .= $this->fetchObjectSizeLimit($project);

        return $repo_config . PHP_EOL;
    }

    public function repoFullName(GitRepository $repo, $unix_name)
    {
        return PathJoinUtil::unixPathJoin(array($unix_name, $repo->getFullName()));
    }

    /**
     * Returns post-receive-email hook config in gitolite format
     *
     * @param Project $project
     * @param GitRepository $repository
     */
    public function fetchMailHookConfig($project, $repository)
    {
        $conf  = '';
        $conf .= ' config hooks.showrev = "';
        $conf .= $repository->getPostReceiveShowRev($this->url_manager);
        $conf .= '"';
        $conf .= PHP_EOL;
        if ($repository->getMailPrefix() != GitRepository::DEFAULT_MAIL_PREFIX) {
            $conf .= ' config hooks.emailprefix = "' . $repository->getMailPrefix() . '"';
            $conf .= PHP_EOL;
        }
        return $conf;
    }

    /**
     * @return string
     */
    private function fetchObjectSizeLimit(Project $project)
    {
        if (! $this->version_detector->isGitolite3()) {
            return "";
        }

        if ($this->bigObjectsAreAuthorizedForProject($project)) {
            return "";
        }

        return ' - VREF/TULEAP_MAX_NEWBIN_SIZE/' . self::OBJECT_SIZE_LIMIT . " = @all" . PHP_EOL;
    }

    /**
     * @return bool
     */
    private function bigObjectsAreAuthorizedForProject(Project $project)
    {
        $authorized_projects = $this->big_object_authorization_manager->getAuthorizedProjects();

        foreach ($authorized_projects as $authorized_project) {
            if ($authorized_project->getID() === $project->getID()) {
                return true;
            }
        }

        return false;
    }
}
