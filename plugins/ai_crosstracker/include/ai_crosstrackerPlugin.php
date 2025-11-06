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
 *
 */

declare(strict_types=1);

use Tuleap\AICrossTracker\REST\v1\TQLAssistantResource;
use Tuleap\CrossTracker\GetCrossTrackerExternalPluginUsage;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../ai/vendor/autoload.php';

final class ai_crosstrackerPlugin extends Plugin // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private const string  AI_CROSSTRACKER_PLUGIN_NAME = 'plugin_ai_crosstracker';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-ai_crosstracker', __DIR__ . '/../site-content');
    }

    #[Override]
    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-ai_crosstracker', 'AI for Cross-Tracker Search'),
                    dgettext('tuleap-ai_crosstracker', 'AI Assistant for Cross-Tracker Search queries'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[Override]
    public function getServiceShortname(): string
    {
        return self::AI_CROSSTRACKER_PLUGIN_NAME;
    }

    #[Override]
    public function getDependencies(): array
    {
        return ['ai', 'crosstracker'];
    }

    #[ListeningToEventClass]
    public function getExternalPlugin(GetCrossTrackerExternalPluginUsage $cross_tracker_external_plugin_usage): void
    {
        $cross_tracker_external_plugin_usage->addServiceNameUsed($this->getServiceShortname());
    }

    #[ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $params['restler']->addAPIClass(TQLAssistantResource::class, TQLAssistantResource::ROUTE);
    }
}
