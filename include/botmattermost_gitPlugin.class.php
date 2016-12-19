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

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\BotMattermostGit\BotGit\BotGitFactory;
use Tuleap\BotMattermostGit\BotGit\BotGitDao;
use Tuleap\BotMattermostGit\Controller;
use Tuleap\BotMattermostGit\Plugin\PluginInfo;
use Tuleap\BotMattermostGit\SenderServices\GitNotificationBuilder;
use Tuleap\BotMattermostGit\SenderServices\GitNotificationSender;


class botmattermost_gitPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->addHook('cssfile');
        if (defined('PLUGIN_BOT_MATTERMOST_BASE_DIR')) {
            require_once PLUGIN_BOT_MATTERMOST_BASE_DIR.'/include/autoload.php';
        }
        if (defined('GIT_BASE_URL')) {
            $this->addHook(GIT_ADDITIONAL_NOTIFICATIONS);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE);
        }
    }

    public function getDependencies()
    {
        return array('git', 'botmattermost');
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
        $logger = new BotMattermostLogger();
        if ($this->isAllowed($repository->getProjectId())) {
            $git_notification_sender = new GitNotificationSender(
                new Sender(
                    new EncoderMessage(),
                    new ClientBotMattermost(),
                    $logger
                ),
                new BotGitFactory(
                    new BotGitDao(),
                    new BotFactory(new BotDao())
                ),
                $repository,
                new GitNotificationBuilder(
                    new Git_GitRepositoryUrlManager(PluginManager::instance()->getPluginByName('git')),
                    $logger
                )
            );

            $git_notification_sender->process($params);
        }
    }

    public function cssfile()
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (strpos($_SERVER['REQUEST_URI'], $git_plugin->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function process()
    {
        $request = HTTPRequest::instance();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getController($request)->save();
        }
    }

    private function getController(HTTPRequest $request)
    {
        $botFactory = new BotFactory(new BotDao());

        return new Controller(
            $request,
            new CSRFSynchronizerToken('/plugins/botmattermost_git/?group_id='.$request->getProject()->getID()),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new BotGitFactory(new BotGitDao(), $botFactory),
            $botFactory
        );
    }
}
