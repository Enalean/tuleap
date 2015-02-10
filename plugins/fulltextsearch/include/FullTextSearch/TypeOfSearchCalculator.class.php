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

class FullTextSearch_TypeOfSearchCalculator {

    /**
     * @var PluginManager
     */
    private $plugin_manager;

    /**
     * @var fulltextsearchPlugin
     */
    private $fulltextsearch_plugin;

    public function __construct(PluginManager $plugin_manager, fulltextsearchPlugin $fulltextsearch_plugin) {
        $this->plugin_manager        = $plugin_manager;
        $this->fulltextsearch_plugin = $fulltextsearch_plugin;
    }

    public function calculate(PFUser $user, $current_type, $service_name, $project_id) {
        if (! $user->useLabFeatures()) {
            return $current_type;
        }

        if ($service_name === Search_SearchWiki::NAME){
            return $this->calculateForWikiService($project_id);
        }

        if ($this->isRequestFromDocmanOrTracker($service_name)) {
            return $this->getFullTextTypeName();
        }

        return $current_type;
    }

    private function calculateForWikiService($project_id) {
        if (! $this->plugin_manager->isPluginAllowedForProject($this->fulltextsearch_plugin, $project_id)) {
            return Search_SearchWiki::NAME;
        }

        return $this->getFullTextTypeName();
    }

    private function isRequestFromDocmanOrTracker($service_name) {
        return $service_name === 'tracker' || $service_name === 'docman';
    }

    private function getFullTextTypeName() {
        return fulltextsearchPlugin::SEARCH_TYPE;
    }
}