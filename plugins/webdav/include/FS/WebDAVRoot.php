<?php
/**
 * Copyright Enalean (c) 2012-Present. All rights reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Sabre\DAV\ICollection;
use Tuleap\Project\ProjectAccessChecker;

/**
 * This is the root of WebDAV virtual filesystem
 *
 * this class lists projects that the user is member of
 */
class WebDAVRoot implements ICollection
{
    public function __construct(
        private WebDAVPlugin $plugin,
        private PFUser $user,
        private int $maxFileSize,
        private ProjectManager $project_manager,
        private WebDAVUtils $utils,
        private PluginManager $plugin_manager,
        private ProjectAccessChecker $project_access_checker,
    ) {
    }

    /**
     * Generates the list of projects that user is member of
     * or all public projects in case the user is anonymous
     * don't generate those for which WebDAV plugin is not available
     *
     * @return \Sabre\DAV\INode[]
     */
    #[\Override]
    public function getChildren(): array
    {
        if ($this->user->isAnonymous()) {
            throw new \Sabre\DAV\Exception\Forbidden(dgettext('tuleap-webdav', 'Anonymous access to webdav is forbidden'));
        }

        return $this->getUserProjectList();
    }

    /**
     * Returns a new WebDAVProject from the given project id
     *
     * @param string $projectName
     */
    #[\Override]
    public function getChild($projectName): WebDAVProject
    {
        $project = $this->project_manager->getProjectByUnixName($projectName);
        if (! $project || $project->isError()) {
            throw new \Sabre\DAV\Exception\NotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'project_not_available'));
        }

        // Check if the project has the active status
        if (! $project->isActive()) {
            // Access denied error
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized'));
        }

        // Check if WebDAV plugin is activated for the project
        if (! $this->isWebDAVAllowedForProject($project)) {
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'plugin_not_available'));
        }

        // Check if the user can access to the project
        // it's important to notice that even if in the listing the user don't see all public projects
        // she still have the right to access to all of them
        if (! $this->userCanRead($project)) {
            // Access denied error
            throw new \Sabre\DAV\Exception\Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'project_access_not_authorized'));
        }

        return $this->getWebDAVProject($project);
    }

    #[\Override]
    public function getName(): string
    {
        return 'WebDAV Root';
    }

    #[\Override]
    public function getLastModified(): int
    {
        return 0;
    }

    /**
     * Returns a new WebDAVProject from the given group Id
     */
    private function getWebDAVProject(Project $project): WebDAVProject
    {
        return new WebDAVProject(
            $this->user,
            $project,
            $this->maxFileSize,
            $this->utils,
        );
    }

    /**
     * Generates project list of the given user
     *
     * @return WebDAVProject[]
     */
    private function getUserProjectList(): array
    {
        $res      = $this->user->getProjects();
        $projects = [];
        foreach ($res as $groupId) {
            $project = $this->project_manager->getProject((int) $groupId);
            if (! $project || $project->isError() || ! $project->isActive()) {
                continue;
            }
            if ($this->isWebDAVAllowedForProject($project)) {
                if ($this->userCanRead($project)) {
                    $projects[] = $this->getWebDAVProject($project);
                }
            }
        }
        return $projects;
    }

    /**
     * Checks whether the WebDAV plugin is available for the project or not
     */
    private function isWebDAVAllowedForProject(Project $project): bool
    {
        return $this->plugin_manager->isPluginAllowedForProject($this->plugin, $project->getID());
    }

    /**
     * Checks whether the user can read the project or not
     */
    private function userCanRead(\Project $project): bool
    {
        try {
            $this->project_access_checker->checkUserCanAccessProject($this->user, $project);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    #[\Override]
    public function delete(): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function setName($name): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function createFile($name, $data = null): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function createDirectory($name): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function childExists($name): bool
    {
        try {
            $this->getChild($name);
            return true;
        } catch (\Sabre\DAV\Exception) {
        }
        return false;
    }
}
