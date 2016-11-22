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
use Tuleap\BotMattermost\BotMattermostLogger;

class GitNotificationBuilder
{

    private $git_repository_url_manager;
    private $logger;

    public function __construct(Git_GitRepositoryUrlManager $git_repository_url_manager, BotMattermostLogger $logger)
    {
        $this->git_repository_url_manager = $git_repository_url_manager;
        $this->logger                     = $logger;
    }

    public function buildNotificationText(array $params)
    {
        $repository = $params['repository'];
        $this->logger->debug('git repository: #'.$repository->getId().' '.$repository->getName());
        $link       = $this->makeLinkReview($repository, $params['newrev']);

        return $params['user']->getName()." ".
        $GLOBALS['Language']->getText('plugin_botmattermost_git', 'push_notification_text').
        " : $link ".$params['refname'];
    }

    private function makeLinkReview(GitRepository $repository, $review)
    {
        $url_review = $repository->getDiffLink($this->git_repository_url_manager, $review);

        return '['.$repository->getName()."]($url_review)";
    }
}