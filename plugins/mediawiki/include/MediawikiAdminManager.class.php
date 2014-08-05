<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class MediawikiAdminManager {

    /** @var MediawikiDao */
    private $dao;

    public function __construct(MediawikiDao $dao) {
        $this->dao = $dao;
    }

    public function getOptions(Project $project) {
        $project_id = $project->getID();

        $options = $this->dao->getAdminOptions($project_id);

        if (! $options) {
            return $this->getDefaultOptions();
        }

        return $options;
    }

    public function getDefaultOptions() {
        return array(
            'enable_compatibility_view' => false,
        );
    }

    public function saveOptions(Project $project, array $options) {
        $project_id                = $project->getID();
        $enable_compatibility_view = (bool) isset($options['enable_compatibility_view']) ? $options['enable_compatibility_view'] : 0;

        return $this->dao->updateAdminOptions($project_id, $enable_compatibility_view);
    }
}