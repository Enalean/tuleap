<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Project;

class ProjectListSearchFieldsPresenterBuilder
{
    private static $ANY = 'ANY';

    public function build(
        $name,
        array $status_values
    ) {
        $status_options = $this->getListOfStatusValuePresenter($status_values);

        return new ProjectListSearchFieldsPresenter($name, $status_options);
    }

    private function getListOfStatusValuePresenter($status_values)
    {
        return [
            $this->getStatusValuePresenter(self::$ANY, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'any')),
            $this->getStatusValuePresenter(Project::STATUS_ACTIVE, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'active')),
            $this->getStatusValuePresenter(Project::STATUS_SYSTEM, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'system')),
            $this->getStatusValuePresenter(Project::STATUS_PENDING, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'pending')),
            $this->getStatusValuePresenter(Project::STATUS_SUSPENDED, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'suspended')),
            $this->getStatusValuePresenter(Project::STATUS_DELETED, $status_values, $GLOBALS['Language']->getText('admin_projectlist', 'deleted')),
        ];
    }

    private function getStatusValuePresenter($status, $status_values, $label)
    {
        $selected = in_array($status, $status_values);

        return [
            'value'       => $status,
            'is_selected' => $selected,
            'label'       => $label
        ];
    }
}
