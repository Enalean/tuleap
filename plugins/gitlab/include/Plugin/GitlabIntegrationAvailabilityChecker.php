<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Plugin;

use gitlabPlugin;
use GitPlugin;
use PluginManager;
use Project;

class GitlabIntegrationAvailabilityChecker
{
    private PluginManager $plugin_manager;
    private gitlabPlugin $gitlab_plugin;

    public function __construct(PluginManager $plugin_manager, gitlabPlugin $gitlab_plugin)
    {
        $this->plugin_manager = $plugin_manager;
        $this->gitlab_plugin  = $gitlab_plugin;
    }

    public function isGitlabIntegrationAvailableForProject(Project $project): bool
    {
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            return false;
        }
        if (! $this->plugin_manager->isPluginAllowedForProject($this->gitlab_plugin, (int) $project->getID())) {
            return false;
        }

        return true;
    }
}
