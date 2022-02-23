<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\MediawikiStandalone\Service\ServiceActivationHandler;
use Tuleap\MediawikiStandalone\Service\ServiceActivationProjectServiceBeforeActivationEvent;
use Tuleap\MediawikiStandalone\Service\ServiceActivationServiceDisabledCollectorEvent;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\ServiceDisabledCollector;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class mediawiki_standalonePlugin extends Plugin
{
    public const SERVICE_SHORTNAME = 'plugin_mediawiki_standalone';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-mediawiki_standalone', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                    '',
                    dgettext('tuleap-mediawiki_standalone', 'Standalone MediaWiki instances integration with Tuleap')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(ProjectServiceBeforeActivation::NAME);
        $this->addHook(ServiceDisabledCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = MediawikiStandaloneService::class;
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl('/to_be_defined');
        }
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationProjectServiceBeforeActivationEvent($event));
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationServiceDisabledCollectorEvent($event));
    }
}
