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

namespace Tuleap\BotMattermostGit;

use HTTPRequest;
use CSRFSynchronizerToken;
use GitRepository;
use GitRepositoryFactory;
use Valid_UInt;
use TemplateRendererFactory;
use Feedback;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermostGit\BotGit\BotGitFactory;
use Tuleap\BotMattermostGit\SenderServices\Sender;
use Tuleap\BotMattermost\Bot\BotFactory;
use PFUser;
use Exception;
use Tuleap\Git\GitViews\RepoManagement\Pane\Notification;


class Controller
{

    private $request;
    private $csrf;
    private $git_repository_factory;
    private $bot_git_factory;
    private $sender;

    public function __construct(
        HTTPRequest           $request,
        CSRFSynchronizerToken $csrf,
        GitRepositoryFactory  $git_repository_factory,
        BotGitFactory         $bot_git_factory,
        Sender                $sender,
        BotFactory            $bot_factory
    ) {
        $this->request                = $request;
        $this->csrf                   = $csrf;
        $this->git_repository_factory = $git_repository_factory;
        $this->bot_git_factory        = $bot_git_factory;
        $this->sender                 = $sender;
        $this->bot_factory             = $bot_factory;
    }

    public function render(GitRepository $repository)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_GIT_BASE_DIR.'/template');
        $presenter_bots = array();
        foreach ($this->bot_git_factory->getBots() as $bot) {
            $presenter_bots[] = $bot->toArray($repository->getId());
        }

        return $renderer->renderToString('index', new Presenter($this->csrf, $repository, $presenter_bots));
    }

    public function save()
    {
        $this->csrf->check();
        $repository_id = $this->request->get('repository_id');
        $repository    = $this->git_repository_factory->getRepositoryById($repository_id);
        if ($this->isValidPostValues()) {
            if ($repository) {
                $bots_ids = $this->request->get('bots_ids') ? $this->request->get('bots_ids') : array();
                $this->saveInDao($repository_id, $bots_ids);
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_hudson_git', 'error_repository_invalid'));
                $GLOBALS['Response']->redirect(GIT_BASE_URL."/?group_id=".$repository->getProjectId());
            }
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost_git', 'alert_invalid_post'));
        }
        $GLOBALS['Response']->redirect(GIT_BASE_URL."/?action=repo_management&group_id=".$repository->getProjectId()."&repo_id=$repository_id&pane=".Notification::ID);
    }

    public function sendNotification(
        GitRepository $repository,
        PFUser $user,
        $newrev,
        $refname
    ) {
        try {
            $bots = $this->bot_git_factory->getBotsByRepositoryId($repository->getId());
        } catch (BotNotFoundException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        }
        $this->sender->pushGitNotifications(
            $bots,
            $repository,
            $user,
            $newrev,
            $refname
        );
    }

    private function saveInDao($repository_id, array $bots_ids)
    {
        try {
            $this->bot_git_factory->saveBotsAssignements($repository_id, $bots_ids);
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_botmattermost_git','alert_success_update'));
        } catch (CannotCreateBotException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        }
    }

    private function isValidPostValues()
    {
        $valid_uint = new Valid_UInt();
        if (
            $this->request->existAndNonEmpty('repository_id') &&
            $valid_uint->validate($this->request->get('repository_id'))
        ) {
            if ($this->request->get('bots_ids')) {
                $bots_ids = $this->request->get('bots_ids');
                return $this->validBotsIds($bots_ids);
            }
            return true;
        }

        return false;
    }

    private function validBotsIds(array $bots_ids)
    {
        $valid_uint = new Valid_UInt();
        try {
            $bots = $this->bot_factory->getBots();
        } catch (Exception $e) {
            return false;
        }
        foreach ($bots_ids as $bot_id) {
            if (!$valid_uint->validate($bot_id) || !$this->validBotId($bots, $bot_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Bot[]
     */
    private function validBotId(array $bots, $bot_id)
    {
        foreach ($bots as $bot) {
            if ($bot->getId() === $bot_id) {
                return true;
            }
        }

        return false;
    }
}