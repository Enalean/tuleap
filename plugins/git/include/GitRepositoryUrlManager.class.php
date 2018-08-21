<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Git_GitRepositoryUrlManager
{
    /** @var GitPlugin  */
    private $git_plugin;

    public function __construct(GitPlugin $git_plugin)
    {
        $this->git_plugin = $git_plugin;
    }

    /**
     * @param GitRepository $repository
     * @return string the base url to access the git repository regarding plugin configuration
     */
    public function getRepositoryBaseUrl(GitRepository $repository)
    {
        if ($this->git_plugin->areFriendlyUrlsActivated()) {
            return GIT_BASE_URL .'/'. $repository->getProject()->getUnixName() .'/'. $repository->getFullName();
        }

        return GIT_BASE_URL . '/index.php/' . $repository->getProjectId() . '/view/' . $repository->getId() . '/';
    }

    public function getRepositoryAdminUrl(GitRepository $repository)
    {
        return $this->git_plugin->getPluginPath() . '/?' . http_build_query(
            [
                'action'   => 'repo_management',
                'group_id' => $repository->getProjectId(),
                'repo_id'  => $repository->getId()
            ]
        );
    }
}
