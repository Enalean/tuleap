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

namespace Tuleap\BotMattermost;

use HTTPRequest;
use CSRFSynchronizerToken;
use TemplateRendererFactory;
use Feedback;
use Valid_HTTPURI;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotAccessDataBaseException;

class AdminController
{

    private $csrf;
    private $bot_factory;

    public function __construct(CSRFSynchronizerToken $csrf, BotFactory $bot_factory)
    {
        $this->csrf        = $csrf;
        $this->bot_factory = $bot_factory;
    }

    public function process(HTTPRequest $request)
    {
        if ($request->getCurrentUser()->isSuperUser()) {
            $action = $request->get('action');
            switch ($action) {
                case 'add_bot':
                    $this->addBot($request);
                    break;
                default:
                    $this->displayIndex();
            }
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_session', 'insufficient_u_access', 'Insufficient User Access'));
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function displayIndex()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_BASE_DIR.'/template');
        try {
            $bots = $this->bot_factory->getBots();
            $admin_presenter = new AdminPresenter($this->csrf, $bots);
            $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('plugin_botmattermost', 'descriptor_name')));
            $renderer->renderToPage('index', $admin_presenter);
            $GLOBALS['HTML']->footer(array());
        } catch (CannotAccessDataBaseException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            $GLOBALS['Response']->redirect('/admin/');
        }
    }

    private function addBot(HTTPRequest $request)
    {
        $this->csrf->check();
        if ($this->validPostArgumentForAddBot($request)) {
            try {
                $this->bot_factory->save($request->get('bot_name'), $request->get('webhook_url'));
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_success_addbot'));
            } catch (CannotCreateBotException $e) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            }
        }
        $this->displayIndex();
    }

    private function validPostArgumentForAddBot(HTTPRequest $request)
    {
        if ($request->existAndNonEmpty('bot_name') && $request->existAndNonEmpty('webhook_url')) {
            return $this->ValidUrl($request->get('webhook_url'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_empty_input'));
            return false;
        }
    }

    private function validUrl($url)
    {
        $valid_url = new Valid_HTTPURI();
        if ($valid_url->validate($url)) {
            return true;
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_invalid_url'));
            return false;
        }
    }
}
