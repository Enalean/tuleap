<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet & Dave Kibble, 2007
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
 *
 * AdminDelegationPlugin
 *
 * This plugin is made of two parts:
 * - The admin one, that allows to delegate some rights (called services to
 *   selected users).
 * - The user one, made of widget in personal page, for the granted (selected)
 *   user to access to the information.
 *
 * Each admin action (grant/revoke) is logged but as of today, the log is only in
 * the database.
 *
 * There is no table dedicated to store services, the services are identified by
 * their id and a label. The id is a constant in the AdminDelegation_Service class.
 *
 * @see AdminDelegation_Service
 *
 */

use FastRoute\RouteCollector;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../vendor/autoload.php';

class AdminDelegationPlugin extends Plugin  // @codingStandardsIgnoreLine
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(CollectRoutesEvent::NAME);
        bindtextdomain('tuleap-admindelegation', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'AdminDelegationPluginInfo')) {
            include_once 'AdminDelegationPluginInfo.class.php';
            $this->pluginInfo = new AdminDelegationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function burningParrotGetJavascriptFiles($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/admindelegation') === 0) {
            $include_assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/admindelegation/scripts',
                '/assets/admindelegation/scripts'
            );

            $params['javascript_files'][] = $include_assets->getFileURL("admin-delegation.js");
        }
    }


    public function burningParrotGetStylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/admindelegation') === 0) {
            $variant = $params['variant'];
            $css_assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/admindelegation/themes/BurningParrot/',
                '/assets/admindelegation/themes/BurningParrot'
            );
            $params['stylesheets'][] = $css_assets->getFileURL('style-' . $variant->getName() . '.css');
        }
    }

    /**
     * Check if current user is allowed to use given widget
     *
     * @param String  $widget
     *
     * @return bool
     */
    protected function userCanViewWidget($widget)
    {
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        if ($user) {
            $service = AdminDelegation_Service::getServiceFromWidget($widget);
            if ($service) {
                $usm = new AdminDelegation_UserServiceManager(
                    new AdminDelegation_UserServiceDao(),
                    new AdminDelegation_UserServiceLogDao()
                );

                return $usm->isUserGrantedForService($user, $service);
            }
        }
        return false;
    }

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    /**
     * Hook: admin link to plugin
     *
     * @param array $params
     */
    public function site_admin_option_hook($params) // @codingStandardsIgnoreLine
    {
        $params['plugins'][] = array(
            'label' => 'Admin delegation',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     * @param \Tuleap\Widget\Event\GetWidget $get_widget_event
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        if ($get_widget_event->getName() === 'admindelegation' && $this->userCanViewWidget('admindelegation')) {
            include_once 'AdminDelegation_UserWidget.class.php';
            $get_widget_event->setWidget(new AdminDelegation_UserWidget($this));
        }
        if ($get_widget_event->getName() ==='admindelegation_projects' && $this->userCanViewWidget('admindelegation_projects')) {
            include_once 'AdminDelegation_ShowProjectWidget.class.php';
            $get_widget_event->setWidget(new AdminDelegation_ShowProjectWidget($this));
        }
    }

    /**
     * Hook: event raised when user lists all available widget
     *
     * @param \Tuleap\Widget\Event\GetUserWidgetList $get_user_widget_list_event
     */
    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $get_user_widget_list_event)
    {
        if ($this->userCanViewWidget('admindelegation')) {
            include_once 'AdminDelegation_UserWidget.class.php';
            $get_user_widget_list_event->addWidget('admindelegation');
        }
        if ($this->userCanViewWidget('admindelegation_projects')) {
            include_once 'AdminDelegation_ShowProjectWidget.class.php';
            $get_user_widget_list_event->addWidget('admindelegation_projects');
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array('admindelegation', 'admindelegation_projects'));
    }

    public function routeAdmin() : \Tuleap\Request\DispatchableWithRequest
    {
        return new \Tuleap\AdminDelegation\SiteAdminController(
            new AdminDelegation_UserServiceManager(
                new AdminDelegation_UserServiceDao(),
                new AdminDelegation_UserServiceLogDao()
            ),
            UserManager::instance(),
            new AdminPageRenderer()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/', $this->getRouteHandler('routeAdmin'));
            $r->addRoute(['GET', 'POST'], '/permissions.php', $this->getRouteHandler('routeAdmin'));
        });
    }
}
