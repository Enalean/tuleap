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

namespace Tuleap\BotMattermostAgileDashboard;

use CSRFSynchronizerToken;
use Exception;
use Feedback;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Factory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Validator;
use Tuleap\BotMattermostAgileDashboard\Presenter\AdminNotificationPresenter;

class Controller
{

    private $request;
    private $csrf;
    private $bot_agiledashboard_factory;
    private $bot_factory;

    public function __construct(
        HTTPRequest $request,
        CSRFSynchronizerToken $csrf,
        Factory $bot_agiledashboard_factory,
        BotFactory $bot_factory,
        Validator $validator
    ) {
        $this->request                    = $request;
        $this->csrf                       = $csrf;
        $this->bot_agiledashboard_factory = $bot_agiledashboard_factory;
        $this->bot_factory                = $bot_factory;
        $this->validator                  = $validator;
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

    public function render()
    {
        $renderer   = TemplateRendererFactory::build()->getRenderer(
            PLUGIN_BOT_MATTERMOST_AGILE_DASHBOARD_BASE_DIR.'/template'
        );
        $project_id = $this->request->getProject()->getID();
        $bots       = $this->bot_factory->getBots();

        if ($bot_assigned = $this->bot_agiledashboard_factory->getBotNotification($project_id)) {
            $bot_assigned = $bot_assigned->toArray($project_id);
        }

        return $renderer->renderToString(
            'adminConfiguration',
            new AdminNotificationPresenter($this->csrf, $bots, $project_id, $bot_assigned)
        );
    }

    private function addBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, 'add')) {
            $project_id = $this->request->getProject()->getID();
            $bot_id     = $this->request->get('bot_id');
            $channels   = explode(',', $this->request->get('channels'));
            $send_time  = $this->request->get('send_time');

            $this->bot_agiledashboard_factory->addBotNotification($channels, $bot_id, $project_id, $send_time);
        }
    }

    private function editBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, 'edit')) {
            $project_id = $this->request->getProject()->getID();
            $channels   = explode(',', $this->request->get('channels'));
            $send_time  = $this->request->get('send_time');

            $this->bot_agiledashboard_factory->saveBotNotification($channels, $project_id, $send_time);
        }
    }

    private function deleteBotNotification()
    {
        if ($this->validator->isValid($this->csrf, $this->request, 'delete')) {
            $project_id = $this->request->getProject()->getID();

            $this->bot_agiledashboard_factory->deleteBotNotification($project_id);
        }
    }

    private function displayIndex()
    {
        $GLOBALS['Response']->redirect(
            AGILEDASHBOARD_BASE_URL.'/?'.http_build_query(
                array(
                    'group_id' => $this->request->getProject()->getID(),
                    'action'   => 'admin',
                    'pane'     => 'notification'
                )
            )
        );
    }

    private function redirectWithErrorFeedback(Exception $e)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        $this->displayIndex();
    }
}
