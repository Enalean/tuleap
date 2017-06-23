<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit\SenderServices;


use Git_GitRepositoryUrlManager;
use GitRepository;
use HTTPRequest;
use PFUser;
use Project;
use Tuleap\PullRequest\PullRequest;

class AttachmentPreTextPresenter
{

    private $pull_request;
    private $user;
    private $request;
    private $project;
    private $repository_destination;
    private $repository_url_manager;

    public $user_name;
    public $user_link;
    public $branch_source;
    public $branch_destination;
    public $pre_text_message;

    /**
     * AttachmentPreTextPresenter constructor.
     * @param PullRequest $pull_request
     * @param PFUser $user
     * @param HTTPRequest $request
     * @param Project $project
     * @param GitRepository $repository_destination
     * @param Git_GitRepositoryUrlManager $repository_url_manager
     */
    public function __construct(
        PullRequest $pull_request,
        PFUser $user,
        HTTPRequest $request,
        Project $project,
        GitRepository $repository_destination,
        Git_GitRepositoryUrlManager $repository_url_manager
    ) {
        $this->pull_request           = $pull_request;
        $this->user                   = $user;
        $this->request                = $request;
        $this->project                = $project;
        $this->repository_destination = $repository_destination;
        $this->repository_url_manager = $repository_url_manager;

        $this->project_name                = $project->getPublicName();
        $this->branch_source               = $pull_request->getBranchSrc();
        $this->branch_destination          = $pull_request->getBranchDest();
        $this->repository_destination_name = $repository_destination->getName();
        $this->repository_destination_link = $this->getRepositoryLink();

        $this->pre_text_message    = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'attachment_pre_text_message', array($user->getName()));
        $this->pre_text_project    = $GLOBALS['Language']->getText('plugin_botmattermost_git', 'attachment_pre_text_project');
    }

    private function getRepositoryLink()
    {
        $repository_base_url = $this->repository_url_manager->getRepositoryBaseUrl($this->repository_destination);

        return $this->request->getServerUrl().$repository_base_url;
    }
}