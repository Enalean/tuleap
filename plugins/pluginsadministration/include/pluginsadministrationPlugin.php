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
use Tuleap\Config\ConfigClassProvider;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\PluginsAdministration\LifecycleHookCommand\PluginUpdateHookCommand;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginsAdministrationPlugin extends PluginWithLegacyInternalRouting implements \Tuleap\Config\PluginWithConfigKeys
{
    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-pluginsadministration', __DIR__ . '/../site-content');
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event): void
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    #[\Override]
    public function &getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'PluginsAdministrationPluginInfo')) {
            require_once('PluginsAdministrationPluginInfo.php');
            $this->pluginInfo = new PluginsAdministrationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[\Override]
    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(\Tuleap\PluginsAdministration\PluginDisablerVerifier::class);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_STYLESHEETS)]
    public function burningParrotGetStylesheets($params): void
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('pluginsadministration-style.css');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES)]
    public function burningParrotGetJavascriptFiles(array $params): void
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['javascript_files'][] = $this->getAssets()->getFileURL('pluginsadministration.js');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            PluginUpdateHookCommand::NAME,
            function (): PluginUpdateHookCommand {
                return new PluginUpdateHookCommand(
                    EventManager::instance(),
                    \Tuleap\CLI\AssertRunner::asHTTPUser(),
                    $this->getBackendLogger()
                );
            }
        );
    }

    #[\Override]
    public function process(): void
    {
        require_once('PluginsAdministration.php');
        $controler = new PluginsAdministration();
        $controler->process();
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/pluginsadministration/'
        );
    }
}
