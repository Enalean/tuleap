<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use Project;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeUpdater;

class AgileDashboardChartsConfigurationUpdater
{
    /**
     * @var CountElementsModeUpdater
     */
    private $count_elements_mode_updater;

    /**
     * @var \Codendi_Request
     */
    private $request;

    public function __construct(\Codendi_Request $request, CountElementsModeUpdater $count_elements_mode_updater)
    {
        $this->count_elements_mode_updater = $count_elements_mode_updater;
        $this->request                     = $request;
    }

    public function updateConfiguration(): void
    {
        $project = $this->request->getProject();

        $use_count_mode = (bool) $this->request->get('burnup-count-mode');
        $this->count_elements_mode_updater->updateBurnupMode($project, $use_count_mode);

        $this->redirectToAdmin($project);
    }

    private function redirectToAdmin(Project $project): void
    {
        $query_parts = [
            'group_id' => $project->getID(),
            'action'   => 'admin',
            'pane'     => 'charts',
        ];

        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?' . http_build_query($query_parts));
    }
}
