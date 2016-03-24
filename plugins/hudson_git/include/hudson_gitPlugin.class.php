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

class hudson_gitPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        if (defined('GIT_BASE_URL')) {
            $this->addHook(GIT_ADDITIONAL_HOOKS);
        }
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('git', 'hudson');
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

    /**
     * @return Hook\HookController
     */
    private function getHookController(Codendi_Request $request) {
        return new Hook\HookController(
            $request,
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new Hook\HookDao()
        );
    }
}
