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

namespace Tuleap\Tracker\Artifact\View;

use Tracker_Artifact_View_View;

class Nature extends Tracker_Artifact_View_View
{

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'hierarchy_title');
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return 'link';
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        return '<div data-artifact-id="' . $this->artifact->getId() . '" class="artifact-nature"></div>';
    }
}
