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

use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermostAgileDashboard\Plugin\PluginInfo;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermostAgileDashboard\Controller;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboardFactory;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboardDao;
use Tuleap\BotMattermostAgileDashboard\SenderServices\MarkdownFormatter;
use Tuleap\BotMattermostAgileDashboard\SenderServices\StandUpNotificationBuilder;
use Tuleap\BotMattermostAgileDashboard\SenderServices\StandUpNotificationSender;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\SenderServices\Sender;

require_once 'autoload.php';
require_once 'constants.php';
require_once PLUGIN_BOT_MATTERMOST_BASE_DIR.'/include/autoload.php';

class botmattermost_agiledashboardPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ADMIN);
        }
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
    }

    public function getDependencies()
    {
        return array('agiledashboard', 'botmattermost');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile()
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
        if (strpos($_SERVER['REQUEST_URI'], $agiledashboard_plugin->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file()
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
        if (strpos($_SERVER['REQUEST_URI'], $agiledashboard_plugin->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/timepicker.js"></script>';
        }
    }

    public function agiledashboard_event_additional_panes_admin(array $params)
    {
        $render = $this->getRenderToString();
        $params['additional_panes']['notification'] = array (
            'title'  => 'Notification',
            'output' => $render,
        );
    }

    public function proccess_system_check()
    {
        $artifact_factory         = Tracker_ArtifactFactory::instance();
        $milestone_status_counter = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $artifact_factory
        );
        $planning_factory = PlanningFactory::build();
        $logger = new BotMattermostLogger();

        $stand_up_notification_sender = new StandUpNotificationSender(
            new BotAgileDashboardFactory(
                new BotAgileDashboardDao,
                new BotFactory(new BotDao()),
                $logger
            ),
            new Sender(
                new EncoderMessage(),
                new ClientBotMattermost(),
                $logger
            ),
            new StandUpNotificationBuilder(
                new Planning_MilestoneFactory(
                    $planning_factory,
                    $artifact_factory,
                    Tracker_FormElementFactory::instance(),
                    TrackerFactory::instance(),
                    $milestone_status_counter,
                    new PlanningPermissionsManager(),
                    new AgileDashboard_Milestone_MilestoneDao()
                ),
                $milestone_status_counter,
                new MarkdownFormatter(),
                $planning_factory,
                new BaseLanguage(ForgeConfig::get('sys_supported_languages'), ForgeConfig::get('sys_lang'))
            ),
            ProjectManager::instance(),
            $logger
        );

        $stand_up_notification_sender->send();
    }

    private function getRenderToString()
    {
        return $this->getController($this->getRequest())->render();
    }

    private function getController(HTTPRequest $request)
    {
        $bot_factory = new BotFactory(new BotDao());
        $project_id  = $request->getProject()->getID();

        return new Controller(
            $request,
            new CSRFSynchronizerToken(AGILEDASHBOARD_BASE_URL.'/?group_id='.$project_id.'&action=admin&pane=notification'),
            new BotAgileDashboardFactory(
                new BotAgileDashboardDao(),
                $bot_factory,
                new BotMattermostLogger()
            ),
            $bot_factory
        );
    }

    private function getRequest()
    {
        return HTTPRequest::instance();
    }

    public function process()
    {
        $request = $this->getRequest();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getController($request)->save();
        }
    }
}
