<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_View_Copy extends Tracker_Artifact_View_Edit
{

    /** @see Tracker_Artifact_View_Edit::getURL() */
    public function getURL()
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            array(
                'aid' => $this->artifact->getId(),
                'func' => 'copy-artifact'
            )
        );
    }

    /** @see Tracker_Artifact_View_Edit::getTitle() */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'edit_title');
    }

    /** @see Tracker_Artifact_View_Edit::fetch() */
    public function fetch()
    {
        $html  = '';
        $html .= '<div class="tracker_artifact">';
        $html .= $this->renderer->fetchFieldsForCopy($this->artifact, $this->request->get('artifact'));
        $html .= '</div>';
        return $html;
    }
}
