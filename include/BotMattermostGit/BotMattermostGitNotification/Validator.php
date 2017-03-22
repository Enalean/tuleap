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

namespace Tuleap\BotMattermostGit\BotMattermostGitNotification;


use CSRFSynchronizerToken;
use Exception;
use Feedback;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use Tuleap\Git\GitViews\RepoManagement\Pane\Notification;
use Valid_UInt;
use Tuleap\BotMattermost\Bot\BotFactory;

class Validator
{

    private $bot_factory;

    public function __construct(BotFactory $bot_factory)
    {
        $this->bot_factory = $bot_factory;
    }

    public function isValid(
        CSRFSynchronizerToken $csrf,
        HTTPRequest $request,
        GitRepositoryFactory $repository_factory,
        $action
    ) {
        $redirect_to = null;

        if ($repository = $repository_factory->getRepositoryById($request->get('repository_id'))) {
            $redirect_to = GIT_BASE_URL.'/?'.http_build_query(
                array(
                    'group_id' => $repository->getProjectId(),
                    'action'   => 'repo_management',
                    'repo_id'  => $repository->getId(),
                    'pane'     => Notification::ID
                )
            );
        }

        $csrf->check($redirect_to);

        if ($request->existAndNonEmpty('repository_id')) {
            if ($this->validId($request->get('repository_id'))) {
                switch ($action) {
                    case 'add':
                        return $this->isValidAddAction($request);
                        break;
                    case 'edit':
                        return $this->isValidEditAction($request);
                        break;
                    case 'delete':
                        return true;
                        break;
                    default:
                        return false;
                }
            }
        }

        return false;
    }

    private function isValidAddAction(HTTPRequest $request)
    {
        if ($request->existAndNonEmpty('bot_id') &&
            $request->exist('channels')
        ) {
            return $this->validBotId($request->get('bot_id'));
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_error_invalid_post_arguments')
        );

        return false;
    }

    private function isValidEditAction(HTTPRequest $request)
    {
        if ($request->exist('channels')) {
            return true;
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'alert_error_invalid_post_arguments')
        );

        return false;
    }

    private function validBotId($bot_id)
    {
        try {
            $this->bot_factory->getBotById($bot_id);
        } catch (Exception $e) {
            return false;
        }

        return $this->validId($bot_id);
    }

    private function validId($id)
    {
        $valid_int = new Valid_UInt();

        return $valid_int->validate($id);
    }
}