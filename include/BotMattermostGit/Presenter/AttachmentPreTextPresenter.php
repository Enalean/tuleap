<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit\Presenter;

use Git_GitRepositoryUrlManager;
use GitRepository;
use PFUser;
use Tuleap\PullRequest\PullRequest;
use Tuleap\ServerHostname;

class AttachmentPreTextPresenter
{
    private $repository_destination;
    private $repository_url_manager;

    public $user_name;
    public $user_link;
    public $branch_source;
    public $branch_destination;
    public $pre_text_message;

    public function __construct(
        PullRequest $pull_request,
        PFUser $user,
        GitRepository $repository_destination,
        Git_GitRepositoryUrlManager $repository_url_manager,
    ) {
        $this->user                   = $user;
        $this->repository_destination = $repository_destination;
        $this->repository_url_manager = $repository_url_manager;

        $this->branch_source               = $pull_request->getBranchSrc();
        $this->branch_destination          = $pull_request->getBranchDest();
        $this->repository_destination_name = $repository_destination->getName();
        $this->repository_destination_link = $this->getRepositoryLink();

        $this->pre_text_message = sprintf(dgettext('tuleap-botmattermost_git', 'Pull request submitted by %1$s on the repository'), $user->getName());
        $this->pre_text_project = dgettext('tuleap-botmattermost_git', 'Project:');
    }

    private function getRepositoryLink()
    {
        $repository_base_url = $this->repository_url_manager->getRepositoryBaseUrl($this->repository_destination);

        return ServerHostname::HTTPSUrl() . $repository_base_url;
    }
}
