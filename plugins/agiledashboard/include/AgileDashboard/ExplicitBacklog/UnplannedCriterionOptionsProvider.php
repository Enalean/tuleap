<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Project;

class UnplannedCriterionOptionsProvider
{
    public const UNPLANNED_IDENTIFIER = "-1";

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(ExplicitBacklogDao $explicit_backlog_dao)
    {
        $this->explicit_backlog_dao = $explicit_backlog_dao;
    }

    public function formatUnplannedAsSelectboxOption(Project $project, $selected_option_id): string
    {
        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID())) {
            return '';
        }

        return $this->getOptionForSelectBox(
            $selected_option_id,
            self::UNPLANNED_IDENTIFIER,
            dgettext('tuleap-agiledashboard', 'Unplanned')
        );
    }

    private function getOptionForSelectBox($selected_option_id, $milestone_id, $label): string
    {
        $selected = '';

        if ($selected_option_id == $milestone_id) {
            $selected = 'selected="selected"';
        }

        $option  = '<option value="' . $milestone_id . '" ' . $selected . '>';
        $option .= $label;
        $option .= '</option>';

        return $option;
    }
}
