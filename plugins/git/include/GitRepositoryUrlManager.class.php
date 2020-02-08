<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\InstanceBaseURLBuilder;

class Git_GitRepositoryUrlManager
{
    /** @var GitPlugin  */
    private $git_plugin;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_base_url_builder;

    public function __construct(GitPlugin $git_plugin, InstanceBaseURLBuilder $instance_base_url_builder)
    {
        $this->git_plugin                = $git_plugin;
        $this->instance_base_url_builder = $instance_base_url_builder;
    }

    /**
     * @return string the base url to access the git repository regarding plugin configuration
     */
    public function getRepositoryBaseUrl(GitRepository $repository)
    {
        return $repository->getRelativeHTTPUrl();
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

    public function getForkUrl(GitRepository $repository)
    {
        return GIT_BASE_URL . "/?" . http_build_query(
            [
                "group_id" => $repository->getProject()->getID(),
                "action"   => "fork_repositories"
            ]
        );
    }

    public function getCommitURL(GitRepository $repository, string $commit_reference): string
    {
        return $this->getRepositoryBaseUrl($repository) . '?' . http_build_query([
            'a' => 'commit',
            'h' => $commit_reference
        ]);
    }

    public function getAbsoluteCommitURL(GitRepository $repository, string $commit_reference): string
    {
        return $this->instance_base_url_builder->build() . $this->getCommitURL($repository, $commit_reference);
    }
}
