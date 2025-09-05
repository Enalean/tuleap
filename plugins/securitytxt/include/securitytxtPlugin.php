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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class securitytxtPlugin extends Plugin implements PluginWithConfigKeys
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-securitytxt', __DIR__ . '/../site-content');
    }

    #[\Override]
    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
            $this->pluginInfo->setPluginDescriptor(
                new PluginDescriptor('security.txt', dgettext('tuleap-securitytxt', 'Add support of security.txt file (RFC 9116)'))
            );
        }
        return $this->pluginInfo;
    }

    #[\Override]
    public function getConfigKeys(\Tuleap\Config\ConfigClassProvider $event): void
    {
        $event->addConfigClass(\Tuleap\SecurityTxt\SecurityTxtOptions::class);
    }

    public function routeGetSecurityTxt(): \Tuleap\SecurityTxt\SecurityTxtController
    {
        return new \Tuleap\SecurityTxt\SecurityTxtController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get(\Tuleap\SecurityTxt\SecurityTxtController::WELL_KNOWN_SECURITY_TXT_HREF, $this->getRouteHandler('routeGetSecurityTxt'));
    }
}
