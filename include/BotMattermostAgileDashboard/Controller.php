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

namespace Tuleap\BotMattermostAgileDashboard;

use CSRFSynchronizerToken;
use Exception;
use Feedback;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboardFactory;
use Tuleap\BotMattermostAgileDashboard\Presenter\AdminNotificationPresenter;
use Valid_UInt;

class Controller
{

    private $request;
    private $csrf;
    private $bot_agiledashboard_factory;
    private $bot_factory;

    public function __construct(
        HTTPRequest              $request,
        CSRFSynchronizerToken    $csrf,
        BotAgileDashboardFactory $bot_agiledashboard_factory,
        BotFactory               $bot_factory
    ) {
        $this->request                    = $request;
        $this->csrf                       = $csrf;
        $this->bot_agiledashboard_factory = $bot_agiledashboard_factory;
        $this->bot_factory                = $bot_factory;
    }

    public function render()
    {
        $renderer       = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_AGILE_DASHBOARD_BASE_DIR.'/template');
        $presenter_bots = array();
        $project_id     = $this->request->getProject()->getID();
        $send_time     = null;

        foreach ($this->bot_agiledashboard_factory->getBotsForTimePeriod($project_id) as $bot) {
            if ($bot->getSendTime()) {
                $send_time = $this->getHoursMinutesFromDate($bot->getSendTime());
            }
            $presenter_bots[] = $bot->toArray($project_id);
        }

        return $renderer->renderToString(
            'adminConfiguration',
            new AdminNotificationPresenter($this->csrf, $presenter_bots, $project_id, $send_time)
        );
    }

    public function save()
    {
        $this->csrf->check();
        $project_id = $this->request->getProject()->getID();

        if ($this->isValidPostValues()) {
            $bots_ids   = $this->request->get('bots_ids') ? $this->request->get('bots_ids') : array();
            $send_time = $this->request->get('send_time');
            $this->saveInDao($bots_ids, $project_id, $send_time);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'alert_success_update')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'alert_invalid_post')
            );
        }
        $GLOBALS['Response']->redirect(AGILEDASHBOARD_BASE_URL.'/?'.http_build_query(array(
            'group_id'  => $project_id,
            'action'    => 'admin',
            'pane'      => 'notification'
        )));
    }

    private function saveInDao(array $bots_ids, $project_id, $send_time)
    {
        try {
            $this->bot_agiledashboard_factory->saveBotsAgileDashboard($bots_ids, $project_id, $send_time);
        } catch (CannotCreateBotException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $ex->getMessage()
            );
        }
    }

    private function isValidPostValues()
    {
        if ($this->request->existAndNonEmpty('send_time')) {
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

    private function getHoursMinutesFromDate($date)
    {
        return date("H:i", strtotime($date));
    }
}
