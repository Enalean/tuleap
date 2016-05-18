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

use Tuleap\HudsonGit\Plugin\PluginInfo;
use Tuleap\HudsonGit\Hook;
use Tuleap\HudsonGit\Logger;
use Tuleap\HudsonGit\Job\JobManager;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\PollingResponseFactory;

class hudson_gitPlugin extends Plugin {

    const DISPLAY_HUDSON_ADDITION_INFO = 'display_hudson_addition_info';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        if (defined('GIT_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook(GIT_ADDITIONAL_HOOKS);
            $this->addHook(GIT_HOOK_POSTRECEIVE);
            $this->addHook(self::DISPLAY_HUDSON_ADDITION_INFO);
        }
    }

    public function display_hudson_addition_info($params)
    {
        $params['installed'] = defined('GIT_BASE_URL');
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('git', 'hudson');
    }

    public function cssFile()
    {
        // Only show the stylesheet if we're actually in the Git pages.
        if (strpos($_SERVER['REQUEST_URI'], GIT_BASE_URL) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function git_additional_hooks(array $params) {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $this->getHookController($params['request'])->renderHook(
                $params['repository'],
                $params['output']
            );
        }
    }

    public function process() {
        $request = HTTPRequest::instance();
        if ($this->isAllowed($request->getProject()->getID())) {
            $this->getHookController($request)->save();
        }
    }

    public function git_hook_post_receive($params)
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $controller = new Hook\HookTriggerController(
                new Hook\HookDao(),
                new Jenkins_Client(
                    new Http_Client()
                ),
                $this->getLogger(),
                new JobManager(new JobDao())
            );
            $controller->trigger($params['repository']);
        }
    }

    /**
     * @return Hook\HookController
     */
    private function getHookController(Codendi_Request $request)
    {
        return new Hook\HookController(
            $request,
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new Hook\HookDao(),
            new JobManager(new JobDao())
        );
    }

    private function getLogger() {
        return new WrapperLogger(new Logger(), 'hudson_git');
    }
}
