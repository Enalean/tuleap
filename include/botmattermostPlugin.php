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

use FastRoute\RouteCollector;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Controller\AdminController;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;

require_once 'constants.php';
require_once 'autoload.php';

class BotMattermostPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->addHook('site_admin_option_hook');
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(CollectRoutesEvent::NAME, 'defaultCollectRoutesEvent');
    }

    /**
     * @return Tuleap\BotMattermost\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\BotMattermost\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function site_admin_option_hook(array $params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_botmattermost', 'descriptor_name'),
            'href'  => $this->getPluginPath() . '/admin/'
        );
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $asset   = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/botmattermost/',
                '/assets/botmattermost'
            );
            $params['javascript_files'][] = $asset->getFileURL('modals.js');
        }
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $variant = $params['variant'];
            $asset   = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/botmattermost/',
                '/assets/botmattermost'
            );
            $params['stylesheets'][] = $asset->getFileURL('style-' . $variant->getName() . '.css');
        }
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/admin/') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function routeAdmin(): \Tuleap\Request\DispatchableWithRequest
    {
        return new Router(
            new AdminController(
                new CSRFSynchronizerToken('/plugins/botmattermost/admin/'),
                new BotFactory(new BotDao()),
                EventManager::instance(),
                $GLOBALS['Language']
            )
        );
    }

    public function defaultCollectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/admin/[index.php]', $this->getRouteHandler('routeAdmin'));
        });
    }
}
