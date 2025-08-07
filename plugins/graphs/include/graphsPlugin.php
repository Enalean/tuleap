<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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


require_once __DIR__ . '/../vendor/autoload.php';
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class graphsPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-graphs', __DIR__ . '/../site-content');
    }

    #[\Override]
    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(new PluginDescriptor(
                dgettext('tuleap-graphs', 'Graphs'),
                dgettext('tuleap-graphs', 'Plugin to show graphs from trackers'),
            ));
            $this->pluginInfo = $plugin_info;
        }
        return $this->pluginInfo;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[\Override]
    public function getServiceShortname(): string
    {
        return 'plugin_graphs';
    }
}
