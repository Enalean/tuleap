<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PluginResourceRestrictor
{

    /**
     * @var RestrictedPluginDao
     */
    private $restricted_plugin_dao;


    public function __construct(RestrictedPluginDao $restricted_plugin_dao)
    {
        $this->restricted_plugin_dao = $restricted_plugin_dao;
    }

    public function isPluginRestricted(Plugin $plugin)
    {
        return $this->restricted_plugin_dao->isResourceRestricted($plugin->getId());
    }

    public function isPluginAllowedForProject(Plugin $plugin, $project_id)
    {
        return $this->restricted_plugin_dao->isPluginAllowedForProject($plugin->getId(), $project_id);
    }

    public function setPluginRestricted(Plugin $plugin)
    {
        return $this->restricted_plugin_dao->setResourceRestricted($plugin->getId());
    }

    public function unsetPluginRestricted(Plugin $plugin)
    {
        return $this->restricted_plugin_dao->unsetResourceRestricted($plugin->getId());
    }

    public function allowProjectOnPlugin(Plugin $plugin, Project $project)
    {
        return $this->restricted_plugin_dao->allowProjectOnResource($plugin->getId(), $project->getId());
    }

    public function revokeProjectsFromPlugin(Plugin $plugin, array $project_ids)
    {
        return $this->restricted_plugin_dao->revokeProjectsFromResource($plugin->getId(), $project_ids);
    }

    public function revokeAllProjectsFromPlugin(Plugin $plugin)
    {
        return $this->restricted_plugin_dao->revokeAllProjectsFromResource($plugin->getId());
    }

    public function searchAllowedProjectsOnPlugin(Plugin $plugin)
    {
        $rows     = $this->restricted_plugin_dao->searchAllowedProjectsOnResource($plugin->getId());
        $projects = [];

        foreach ($rows as $row) {
            $projects[] = new Project($row);
        }

        return $projects;
    }
}
