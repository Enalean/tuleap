<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit;


use CSRFSynchronizerToken;
use Exception;
use Feedback;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Factory;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Validator;
use Tuleap\Git\GitViews\RepoManagement\Pane\Notification;

class Controller
{

    private $request;
    private $csrf;
    private $git_repository_factory;
    private $bot_git_factory;
    private $bot_factory;

    public function __construct(
        HTTPRequest $request,
        CSRFSynchronizerToken $csrf,
        GitRepositoryFactory $git_repository_factory,
        Factory $bot_git_factory,
        BotFactory $bot_factory,
        Validator $validator
    ) {
        $this->request                = $request;
        $this->csrf                   = $csrf;
        $this->git_repository_factory = $git_repository_factory;
        $this->bot_git_factory        = $bot_git_factory;
        $this->bot_factory            = $bot_factory;
        $this->validator              = $validator;
    }

    public function process()
    {
        $action = $this->request->get('action');

        try {
            switch ($action) {
                case 'add_bot':
                    $this->addBotNotification();
                    break;
                case 'edit_bot':
                    $this->editBotNotification();
                    break;
                case 'delete_bot':
                    $this->deleteBotNotification();
                    break;
                default:
                    $this->displayIndex();
            }

            $this->displayIndex();
        } catch (Exception $exception) {
            $this->redirectWithErrorFeedback($exception);
        }
    }

    public function render(GitRepository $repository)
    {
        $renderer      = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_GIT_BASE_DIR.'/template');
        $repository_id = $repository->getId();
        $bots          = $this->bot_factory->getBots();

        if ($bot_assigned = $this->bot_git_factory->getBotNotification($repository_id)) {
            $bot_assigned = $bot_assigned->toArray($repository_id);
        }

        return $renderer->renderToString(
            'index',
            new Presenter($this->csrf, $repository, $bots, $bot_assigned)
        );
    }

    private function displayIndex()
    {
        $repository = $this->git_repository_factory->getRepositoryById($this->request->get('repository_id'));

        $GLOBALS['Response']->redirect(
            GIT_BASE_URL.'/?action=repo_management&group_id='.$repository->getProjectId(
            ).'&repo_id='.$repository->getId().'&pane='.Notification::ID
        );
    }

    private function addBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, $this->git_repository_factory, 'add')) {
            $repository_id = $this->request->get('repository_id');
            $bot_id        = $this->request->get('bot_id');
            $channels      = explode(',', $this->request->get('channels'));

            $this->bot_git_factory->addBotNotification($channels, $repository_id, $bot_id);
        }
    }

    private function editBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, $this->git_repository_factory, 'edit')) {
            $repository_id = $this->request->get('repository_id');
            $channels      = explode(',', $this->request->get('channels'));

            $this->bot_git_factory->saveBotNotification($channels, $repository_id);
        }
    }

    private function deleteBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, $this->git_repository_factory, 'delete')) {
            $repository_id = $this->request->get('repository_id');

            $this->bot_git_factory->deleteBotNotification($repository_id);
        }
    }

    private function redirectWithErrorFeedback(Exception $e)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        $this->displayIndex();
    }
}
