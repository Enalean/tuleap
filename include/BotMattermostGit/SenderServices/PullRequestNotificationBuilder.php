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

use GitRepository;
use Git_GitRepositoryUrlManager;
use HTTPRequest;
use PFUser;
use Project;
use TemplateRendererFactory;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\PullRequest\PullRequest;

class PullRequestNotificationBuilder
{

    private $logger;
    private $repository_url_manager;

    public function __construct(BotMattermostLogger $logger, Git_GitRepositoryUrlManager $repository_url_manager)
    {
        $this->logger                 = $logger;
        $this->repository_url_manager = $repository_url_manager;
    }

    public function buildNotificationAttachment(
        PullRequest $pull_request,
        PFUser $user,
        HTTPRequest $request,
        Project $project,
        GitRepository $repository_destination
    ) {
        $text       = $this->makeText($pull_request->getDescription());
        $title_link = $this->makeTitleLink($pull_request, $request, $project);
        $pretext    = $this->makePreText(
            $pull_request,
            $user,
            $request,
            $project,
            $repository_destination
        );

        return new Attachment($pretext, $pull_request->getTitle(), $title_link, $text);
    }

    private function makePreText(
        PullRequest $pull_request,
        PFUser $user,
        HTTPRequest $request,
        Project $project,
        GitRepository $repository_destination
    ) {
        $renderer =  TemplateRendererFactory::build()->getRenderer(
            PLUGIN_BOT_MATTERMOST_GIT_BASE_DIR.'/template/attachment'
        );

        return $renderer->renderToString(
            'pretext',
            new AttachmentPreTextPresenter(
                $pull_request,
                $user,
                $request,
                $project,
                $repository_destination,
                $this->repository_url_manager
        ));
    }

    private function makeText($pull_request_description)
    {
        $text = '';
        if (!empty($pull_request_description)) {
            $text = $pull_request_description;
        }

        return $text;
    }

    private function makeTitleLink(PullRequest $pull_request, HTTPRequest $request, Project $project)
    {
        return $request->getServerUrl() . GIT_BASE_URL . '/?' . http_build_query(
                array(
                    'action'   => 'pull-requests',
                    'repo_id'  => $pull_request->getRepositoryId(),
                    'group_id' => $project->getID(),
                )) . '#/pull-requests/' . $pull_request->getId() . '/overview';
    }
}