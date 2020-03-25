<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginsAdministrationPlugin extends PluginWithLegacyInternalRouting
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->listenToCollectRouteEventWithDefaultController();
        bindtextdomain('tuleap-pluginsadministration', __DIR__ . '/../site-content');
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function &getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'PluginsAdministrationPluginInfo')) {
            require_once('PluginsAdministrationPluginInfo.class.php');
            $this->pluginInfo = new PluginsAdministrationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function burning_parrot_get_stylesheets($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $variant                 = $params['variant'];
            \assert($variant instanceof ThemeVariantColor);
            $params['stylesheets'][] = $this->getAssets()->getFileURL('style-' . $variant->getName() . '.css');
        }
    }

    public function burning_parrot_get_javascript_files(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
            $params['javascript_files'][] = $this->getAssets()->getFileURL('pluginsadministration.js');
        }
    }

    public function process() : void
    {
        require_once('PluginsAdministration.class.php');
        $controler = new PluginsAdministration();
        $controler->process();
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/pluginsadministration/',
            '/assets/pluginsadministration/'
        );
    }
}
