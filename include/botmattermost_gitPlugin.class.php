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

use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Dao;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Factory;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Validator;
use Tuleap\BotMattermostGit\Plugin\PluginInfo;
use Tuleap\BotMattermostGit\Controller;
use Tuleap\BotMattermostGit\SenderServices\GitNotificationBuilder;
use Tuleap\BotMattermostGit\SenderServices\GitNotificationSender;
use Tuleap\BotMattermostGit\SenderServices\PullRequestNotificationBuilder;
use Tuleap\BotMattermostGit\SenderServices\PullRequestNotificationSender;
use Tuleap\PullRequest\GetCreatePullRequest;

require_once 'autoload.php';
require_once 'constants.php';
require_once __DIR__ . '/../../botmattermost/include/botmattermostPlugin.class.php';
require_once __DIR__ . '/../../git/include/gitPlugin.class.php';

class botmattermost_gitPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        if (defined('PLUGIN_BOT_MATTERMOST_BASE_DIR')) {
            require_once PLUGIN_BOT_MATTERMOST_BASE_DIR.'/include/autoload.php';
        }
    }

    public function getDependencies()
    {
        return array('git', 'botmattermost');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('javascript_file');

        if (defined('GIT_BASE_URL')) {
            $this->addHook(GIT_ADDITIONAL_NOTIFICATIONS);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE);
        }
        if (defined('PULLREQUEST_BASE_DIR')) {
            $this->addHook(GetCreatePullRequest::NAME);
        }
        $this->addHook(BotMattermostDeleted::NAME);

        return parent::getHooksAndCallbacks();
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

    public function getServiceShortname()
    {
        return 'plugin_botmattermost_git';
    }

    public function git_additional_notifications(array $params)
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $render = $this->getController($params['request'])->render($params['repository']);
            $params['output'] .= $render;
        }
    }

    public function git_hook_post_receive_ref_update(array $params)
    {
        $repository = $params['repository'];
        $logger = $this->getLogger();
        if ($this->isAllowed($repository->getProjectId())) {
            $git_notification_sender = new GitNotificationSender(
                $this->getSender($logger),
                $this->getFactory(),
                $repository,
                new GitNotificationBuilder(
                    $this->getGitRepositoryUrlManager(),
                    $logger
                )
            );

            $git_notification_sender->process($params);
        }
    }

    public function pullrequest_hook_create_pull_request(GetCreatePullRequest $event)
    {
        $pull_request           = $event->getPullRequest();
        $creator                = $event->getCreator();
        $project                = $event->getProject();
        $logger                 = new BotMattermostLogger();
        $repository_destination = $this->getGitRepositoryFactory()->getRepositoryById($pull_request->getRepoDestId());

        if ($this->isAllowed($project->getID())) {
            $pull_request_notification_sender = new PullRequestNotificationSender(
                $this->getSender($logger),
                $this->getFactory(),
                new PullRequestNotificationBuilder($logger,  $this->getGitRepositoryUrlManager()),
                $this->getLogger()
            );

            $pull_request_notification_sender->send(
                $pull_request,
                $creator,
                HTTPRequest::instance(),
                $project,
                $repository_destination
            );
        }
    }

    public function cssfile()
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (strpos($_SERVER['REQUEST_URI'], $git_plugin->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file()
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (strpos($_SERVER['REQUEST_URI'], $git_plugin->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/autocompleter.js"></script>';
        }
    }

    public function botmattermost_bot_deleted(BotMattermostDeleted $event)
    {
        $this->getController(HTTPRequest::instance())->deleteBotNotificationByBot($event->getBot());
    }

    public function process()
    {
        $request = HTTPRequest::instance();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getController($request)->process();
        }
    }

    private function getController(HTTPRequest $request)
    {
        $botFactory = new BotFactory(new BotDao());

        return new Controller(
            $request,
            new CSRFSynchronizerToken('/plugins/botmattermost_git/?group_id='.$request->getProject()->getID()),
            $this->getGitRepositoryFactory(),
            new Factory(new Dao(), $botFactory),
            $botFactory,
            new Validator($botFactory)
        );
    }

    private function getGitRepositoryUrlManager()
    {
        return new Git_GitRepositoryUrlManager(PluginManager::instance()->getPluginByName('git'));
    }

    private function getGitRepositoryFactory()
    {
        return new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
    }

    private function getSender(BotMattermostLogger $logger)
    {
        return new Sender(
            new EncoderMessage(),
            new ClientBotMattermost(),
            $logger
        );
    }

    private function getFactory()
    {
        return new Factory(new Dao(), new BotFactory(new BotDao()));
    }

    private function getLogger()
    {
        return new BotMattermostLogger();
    }
}
