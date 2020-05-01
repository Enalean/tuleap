<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Artifact\Renderer\GetAdditionalJavascriptFilesForArtifactDisplay;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class embedPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-embed', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        if (defined('TRACKER_BASE_DIR')) {
            $this->addHook(GetAdditionalJavascriptFilesForArtifactDisplay::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-embed', 'Embed'),
                    '',
                    dgettext('tuleap-embed', 'Embed various services in artifacts.')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getAdditionalJavascriptFilesForArtifactDisplay(
        GetAdditionalJavascriptFilesForArtifactDisplay $event
    ): void {
        $include_assets = new IncludeAssets(__DIR__ . '/../../../src/www/assets/embed', '/assets/embed');
        $event->add($include_assets->getFileURL('embed.js'));
    }
}
