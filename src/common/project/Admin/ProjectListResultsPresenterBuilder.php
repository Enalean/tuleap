<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use DataAccessResult;
use Project;
use TemplateSingleton;

class ProjectListResultsPresenterBuilder
{
    public function build(
        DataAccessResult $projects,
        $nb_matching_projects,
        $group_name_search,
        $status_values,
        $limit,
        $offset
    ) {
        $matching_projects = array();

        foreach ($projects as $row) {
            list($status_label, $status_class) = $this->getStatusDisplay($row['status']);
            $visibility_label                  = $this->getVisibilityLabel($row['access']);
            $type_label                        = $this->getTypeLabel($row['type']);
            $project_name                      = util_unconvert_htmlspecialchars($row['group_name']);

            $matching_projects[] = new ProjectListResultsProjectPresenter(
                $row['group_id'],
                $project_name,
                $row['unix_group_name'],
                $status_label,
                $status_class,
                $type_label,
                $visibility_label,
                $row['nb_members']
            );
        }

        return new ProjectListResultsPresenter(
            $matching_projects,
            $nb_matching_projects,
            $group_name_search,
            $status_values,
            $limit,
            $offset
        );
    }

    /**
     * @param  string $status_letter
     * @return array
     */
    private function getStatusDisplay($status_letter)
    {
        switch ($status_letter) {
            case Project::STATUS_ACTIVE:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'active');
                $status_class = 'tlp-badge-success';
                break;
            case Project::STATUS_SYSTEM:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'system');
                $status_class = 'tlp-badge-danger tlp-badge-outline';
                break;
            case Project::STATUS_PENDING:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'pending');
                $status_class = 'tlp-badge-secondary';
                break;
            case Project::STATUS_INCOMPLETE:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'incomplete');
                $status_class = 'tlp-badge-warning';
                break;
            case Project::STATUS_HOLDING:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'holding');
                $status_class = 'tlp-badge-info';
                break;
            case Project::STATUS_DELETED:
                $status_label = $GLOBALS['Language']->getText('admin_projectlist', 'deleted');
                $status_class = 'tlp-badge-danger';
                break;
        }

        return array($status_label, $status_class);
    }

    /**
     * @param  string $visibility
     * @return string
     */
    private function getVisibilityLabel($visibility)
    {
        switch ($visibility) {
            case Project::ACCESS_PRIVATE:
                return $GLOBALS['Language']->getText('admin_projectlist', 'private');
            case Project::ACCESS_PUBLIC_UNRESTRICTED:
                return $GLOBALS['Language']->getText('admin_projectlist', 'public_unrestricted');
            case Project::ACCESS_PUBLIC:
                return $GLOBALS['Language']->getText('admin_projectlist', 'public');
        }
    }

    /**
     * @param  string $type
     * @return string
     */
    private function getTypeLabel($type)
    {
        $localized_types = TemplateSingleton::instance()->getLocalizedTypes();
        return $localized_types[$type];
    }
}
