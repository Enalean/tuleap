<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
use Tuleap\Userlog\UserLogBuilder;
use Tuleap\Userlog\UserLogExporter;
use Tuleap\Userlog\UserLogRouter;

require_once 'constants.php';
require_once __DIR__. '/../vendor/autoload.php';

class userlogPlugin extends Plugin implements \Tuleap\Request\DispatchableWithRequest
{

    function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(Event::HIT, 'logUser', false);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/userlog') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/userlog') === 0) {
            $params['javascript_files'][] = $this->getPluginPath() .'/scripts/user-logging-date-picker.js';
        }
    }

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo = new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
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

    public function logUser($params)
    {
        if(! $params['is_script']) {
            $request = $params['request'];

            $userLogManager = new UserLogManager(new AdminPageRenderer(), UserManager::instance());
            $userLogManager->logAccess($_SERVER['REQUEST_TIME'],
                                       $request->getProject()->getID(),
                                       $request->getCurrentUser()->getId(),
                                       $request->getFromServer('HTTP_USER_AGENT'),
                                       $request->getFromServer('REQUEST_METHOD'),
                                       $request->getFromServer('REQUEST_URI'),
                                       HTTPRequest::instance()->getIPAddress(),
                                       $request->getFromServer('HTTP_REFERER'));
        }
    }

    public function process(HTTPRequest $request, \Tuleap\Layout\BaseLayout $layout, array $variables)
    {
        $router = new UserLogRouter(
            new UserLogExporter(new UserLogBuilder(new UserLogDao(), UserManager::instance())),
            new UserLogManager(new AdminPageRenderer(), UserManager::instance())
        );

        $router->route($request);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addRoute(['GET', 'POST'], '/plugins/userlog[/]', function () {
           return $this;
        });
    }
}
