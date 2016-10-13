<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use GitRepository;
use Git_GitRepositoryUrlManager;
use Tuleap\BotMattermost\SenderServices\NotificationBuilder;

class GitNotificationBuilder implements NotificationBuilder
{

    private $git_repository_url_manager;
    private $params;

    public function __construct(
        Git_GitRepositoryUrlManager $git_repository_url_manager,
        array $params
    ) {
        $this->git_repository_url_manager = $git_repository_url_manager;
        $this->params                     = $params;
    }

    public function buildNotificationText()
    {
        $repository = $this->params['repository'];
        $link       = $this->makeLinkReview($repository, $this->params['newrev']);

        return $this->params['user']->getName()." ".
            $GLOBALS['Language']->getText('plugin_botmattermost_git', 'push_notification_text').
            " : $link ".$this->params['refname'];
    }

    private function makeLinkReview(GitRepository $repository, $review)
    {
        $url_review = $repository->getDiffLink($this->git_repository_url_manager, $review);

        return '['.$repository->getName()."]($url_review)";
    }
}