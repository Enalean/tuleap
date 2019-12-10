<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Event\Events\HitEvent;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Userlog\UserlogAccess;
use Tuleap\Userlog\UserlogAccessStorage;
use Tuleap\Userlog\UserLogBuilder;
use Tuleap\Userlog\UserLogExporter;
use Tuleap\Userlog\UserLogRouter;

require_once 'constants.php';
require_once __DIR__. '/../vendor/autoload.php';

class userlogPlugin extends Plugin implements \Tuleap\Request\DispatchableWithRequest //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(HitEvent::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
        $this->addHook(ProjectProviderEvent::NAME);
    }

    public function burning_parrot_get_stylesheets($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/userlog') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/userlog') === 0) {
            $params['javascript_files'][] = $this->getPluginPath() .'/scripts/user-logging-date-picker.js';
        }
    }

    public function &getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo = new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    public function siteAdminHooks($params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_userlog', 'descriptor_name'),
            'href'  => $this->getPluginPath() . '/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function hitEvent(HitEvent $event)
    {
        if ($event->isScript() === true) {
            return;
        }

        $request = $event->getRequest();

        $userLogManager = new UserLogManager(new AdminPageRenderer(), UserManager::instance());
        $userlog_access = UserlogAccess::buildFromRequest($request);

        if ($userlog_access->hasProjectIdDefined()) {
            $userLogManager->logAccess($userlog_access);
            return;
        }

        $userlog_access_storage = UserlogAccessStorage::instance();
        $userlog_access_storage->storeUserlogAccess($userlog_access);

        register_shutdown_function(function () {
            $userlog_access_storage = UserlogAccessStorage::instance();
            $userlog_access = $userlog_access_storage->getUserlogAccess();

            if ($userlog_access === null) {
                return;
            }

            $user_log_manager = new UserLogManager(new AdminPageRenderer(), UserManager::instance());
            $user_log_manager->logAccess($userlog_access);
        });
    }

    public function projectProviderEvent(ProjectProviderEvent $event): void
    {
        $userlog_access_storage = UserlogAccessStorage::instance();
        $userlog_access = $userlog_access_storage->getUserlogAccess();

        if ($userlog_access === null) {
            return;
        }

        $userlog_access->setProject($event->getProject());
        $userlog_access_storage->storeUserlogAccess($userlog_access);
    }

    public function process(HTTPRequest $request, \Tuleap\Layout\BaseLayout $layout, array $variables)
    {
        $router = new UserLogRouter(
            new UserLogExporter(new UserLogBuilder(new UserLogDao(), UserManager::instance())),
            new UserLogManager(new AdminPageRenderer(), UserManager::instance())
        );

        $router->route($request);
    }

    public function routeSlash(): userlogPlugin
    {
        return $this;
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addRoute(['GET', 'POST'], '/plugins/userlog[/]', $this->getRouteHandler('routeSlash'));
    }
}
