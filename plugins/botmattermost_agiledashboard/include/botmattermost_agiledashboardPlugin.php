<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminPaneContent;
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownTemplateRendererFactory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Dao;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\Factory;
use Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary\NotificationCreator;
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

require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../botmattermost/include/botmattermostPlugin.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

class botmattermost_agiledashboardPlugin extends \Tuleap\Plugin\PluginWithLegacyInternalRouting //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-botmattermost_agiledashboard', __DIR__ . '/../site-content');
    }

    #[Override]
    public function getHooksAndCallbacks()
    {
        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(GetAdditionalScrumAdminPaneContent::NAME);
        }
        $this->addHook(EventCronJobEveryMinute::NAME);
        $this->addHook(BotMattermostDeleted::NAME);

        return parent::getHooksAndCallbacks();
    }

    #[Override]
    public function getDependencies()
    {
        return ['agiledashboard', 'botmattermost'];
    }

    /**
     * @return PluginInfo
     */
    #[Override]
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function additional_scrum_admin_pane_content(GetAdditionalScrumAdminPaneContent $event) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $render = $this->getRenderToString();
        $event->addContent($render);
    }

    public function cron_job_every_minute(EventCronJobEveryMinute $event) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $artifact_factory         = Tracker_ArtifactFactory::instance();
        $milestone_status_counter = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new BacklogItemDao(),
            new \Tuleap\Tracker\Artifact\Dao\ArtifactDao(),
            $artifact_factory
        );
        $planning_factory         = PlanningFactory::build();
        $logger                   = new BotMattermostLogger();

        $planning_milestone_factory = Planning_MilestoneFactory::build();

        $stand_up_notification_builder = new StandUpNotificationBuilder(
            $planning_milestone_factory,
            $milestone_status_counter,
            $planning_factory,
            MarkdownTemplateRendererFactory::build()
                ->getRenderer(PLUGIN_BOT_MATTERMOST_AGILE_DASHBOARD_BASE_DIR . '/templates')
        );

        $bot_agiledashboard_factory = new Factory(
            new Dao(),
            new BotFactory(new BotDao()),
            $logger
        );

        $sender = new Sender(
            new EncoderMessage(),
            new ClientBotMattermost(),
            $logger
        );

        $stand_up_notification_sender = new StandUpNotificationSender(
            $bot_agiledashboard_factory,
            $sender,
            $stand_up_notification_builder,
            ProjectManager::instance(),
            $logger
        );

        $stand_up_notification_sender->send();
    }

    public function botmattermost_bot_deleted(BotMattermostDeleted $event) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
            new CSRFSynchronizerToken(AGILEDASHBOARD_BASE_URL . '/?group_id=' . $project_id . '&action=admin&pane=notification'),
            new Factory(
                new Dao(),
                $bot_factory,
                new BotMattermostLogger()
            ),
            $bot_factory,
            new Validator($bot_factory),
            new NotificationCreator(
                new Dao(),
                new \Tuleap\BotMattermost\Bot\BotValidityChecker()
            )
        );
    }

    private function getRequest()
    {
        return HTTPRequest::instance();
    }

    #[Override]
    public function process(): void
    {
        $request = $this->getRequest();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getController($request)->process();
        }
    }
}
