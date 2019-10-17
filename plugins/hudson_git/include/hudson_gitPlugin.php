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

require_once __DIR__ . '/../../hudson/include/hudsonPlugin.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\HudsonGit\Plugin\PluginInfo;
use Tuleap\HudsonGit\Hook;
use Tuleap\HudsonGit\Logger;
use Tuleap\HudsonGit\Job\JobManager;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\GitWebhooksSettingsEnhancer;
use Tuleap\Git\GitViews\RepoManagement\Pane\Hooks;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;

class hudson_gitPlugin extends PluginWithLegacyInternalRouting //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const DISPLAY_HUDSON_ADDITION_INFO = 'display_hudson_addition_info';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->listenToCollectRouteEventWithDefaultController();

        if (defined('GIT_BASE_URL')) {
            $this->addHook(Hooks::ADDITIONAL_WEBHOOKS);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE);
            $this->addHook(self::DISPLAY_HUDSON_ADDITION_INFO);
        }
    }

    public function display_hudson_addition_info($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['installed'] = defined('GIT_BASE_URL');
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('git', 'hudson');
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

    /** @see Tuleap\Git\GitViews\RepoManagement\Pane\Hooks::ADDITIONAL_WEBHOOKS */
    public function plugin_git_settings_additional_webhooks(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $xzibit = new GitWebhooksSettingsEnhancer(
                new Hook\HookDao(),
                new JobManager(new JobDao()),
                $this->getCSRF()
            );
            $xzibit->pimp($params);
        }
    }

    public function process() : void
    {
        $request = HTTPRequest::instance();
        $action  = $request->get('action');

        if (! $this->isAllowed($request->getProject()->getID())) {
            return;
        }

        switch ($action) {
            case 'save-jenkins':
                $this->getHookController($request)->save();
                break;

            case 'remove-webhook':
                if ($request->get('webhook_id') === 'jenkins') {
                    $this->getHookController($request)->remove();
                }
                break;
        }
    }

    public function git_hook_post_receive_ref_update($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $http_client     = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
            $request_factory = HTTPFactoryBuilder::requestFactory();
            $controller      = new Hook\HookTriggerController(
                new Hook\HookDao(),
                new Hook\JenkinsClient(
                    $http_client,
                    $request_factory,
                    new JenkinsCSRFCrumbRetriever($http_client, $request_factory)
                ),
                $this->getLogger(),
                new JobManager(new JobDao())
            );
            $controller->trigger($params['repository'], $params['newrev']);
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
            $this->getCSRF()
        );
    }

    private function getCSRF()
    {
        return new CSRFSynchronizerToken('hudson-git-hook-management');
    }

    private function getLogger()
    {
        return new WrapperLogger(new Logger(), 'hudson_git');
    }
}
