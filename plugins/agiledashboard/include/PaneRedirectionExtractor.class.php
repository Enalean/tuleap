<?php
/**
 * Copyright Enalean (c) 2013-2019. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_PaneRedirectionExtractor
{
    public const ARTIFACT_ID = 'aid';
    public const PANE        = 'pane';
    public const PLANNING_ID = 'planning_id';
    public const ACTION      = 'action';

    /**
     * Get the parameters to redirect to proper pane on the AgileDashboard
     * @return array || null
     */
    public function getRedirectToParameters(Codendi_Request $request, Project $project)
    {
        $request_parameters = $this->extractParametersFromRequest($request);

        if ($request_parameters) {
            $request_parameters['group_id'] = $project->getGroupId();
        }

        return $request_parameters;
    }

    /**
     * Extract the redirection parameters contained in the request
     * @return array || null containing pane, planning_id, artifact_id and action
     */
    public function extractParametersFromRequest(Codendi_Request $request)
    {
        $planning = $request->get('planning');
        if (! is_array($planning) || ! count($planning)) {
            return;
        }
        $pane_identifier = key($planning);
        $from_planning   = current($planning);
        if (is_array($from_planning) && count($from_planning)) {
            $planning_id          = key($from_planning);
            $planning_artifact_id = current($from_planning);
            return array(
                self::PANE        => $pane_identifier,
                self::PLANNING_ID => $planning_id,
                self::ARTIFACT_ID => $planning_artifact_id,
                self::ACTION      => 'show'
            );
        }
    }
}
