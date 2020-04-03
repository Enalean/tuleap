<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\Breadcrumbs;

use Project;

class AdmininistrationBreadcrumbs implements Breadcrumbs
{
    /**
     * @return array
     */
    public function getCrumbs(Project $project): array
    {
        $home_url = TESTMANAGEMENT_BASE_URL . '/?' . http_build_query(array(
            'group_id' => $project->getID()
        ));

        $administration_url = TESTMANAGEMENT_BASE_URL . '/?' . http_build_query(array(
           'group_id' => $project->getID(),
            'action'  => 'admin'
        ));

        return array(
            array(
                'title' => 'TestManagement',
                'url'   => $home_url
            ),
            array(
                'title' => 'Administration',
                'url'   => $administration_url
            )
        );
    }
}
