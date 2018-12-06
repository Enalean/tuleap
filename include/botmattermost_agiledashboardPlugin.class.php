<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminPaneContent;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownTemplateRendererFactory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Dao;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Factory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Validator;
use Tuleap\BotMattermostAgileDashboard\Plugin\PluginInfo;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermostAgileDashboard\Controller;
use Tuleap\BotMattermostAgileDashboard\SenderServices\StandUpNotificationBuilder;
use Tuleap\BotMattermostAgileDashboard\SenderServices\StandUpNotificationSender;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\Cron\EventCronJobEveryMinute;

require_once 'autoload.php';
require_once 'constants.php';
require_once __DIR__ . '/../../botmattermost/include/botmattermostPlugin.class.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.class.php';

class botmattermost_agiledashboardPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function getHooksAndCallbacks()
    {
        if (defined('PLUGIN_BOT_MATTERMOST_BASE_DIR')) {
            require_once PLUGIN_BOT_MATTERMOST_BASE_DIR.'/include/autoload.php';
        }
        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook(GetAdditionalScrumAdminPaneContent::NAME);
        }
        $this->addHook(EventCronJobEveryMinute::NAME);
        $this->addHook(BotMattermostDeleted::NAME);

        return parent::getHooksAndCallbacks();
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
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/autocompleter.js"></script>';
        }
    }

    public function additional_scrum_admin_pane_content(GetAdditionalScrumAdminPaneContent $event)
    {
        $render = $this->getRenderToString();
        $event->addContent($render);
    }

    public function cron_job_every_minute(EventCronJobEveryMinute $event)
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
            new Factory(
                new Dao,
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
                    new AgileDashboard_Milestone_MilestoneDao(),
                    new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory)
                ),
                $milestone_status_counter,
                $planning_factory,
                new BaseLanguage(ForgeConfig::get('sys_supported_languages'), ForgeConfig::get('sys_lang')),
                MarkdownTemplateRendererFactory::build()
                    ->getRenderer(PLUGIN_BOT_MATTERMOST_AGILE_DASHBOARD_BASE_DIR.'/template')
            ),
            ProjectManager::instance(),
            $logger
        );

        $stand_up_notification_sender->send($this->getRequest());
    }

    public function botmattermost_bot_deleted(BotMattermostDeleted $event)
    {
        $this->getController(HTTPRequest::instance())->deleteBotNotificationByBot($event->getBot());
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
            new Factory(
                new Dao(),
                $bot_factory,
                new BotMattermostLogger()
            ),
            $bot_factory,
            new Validator($bot_factory)
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
            $this->getController($request)->process();
        }
    }
}
