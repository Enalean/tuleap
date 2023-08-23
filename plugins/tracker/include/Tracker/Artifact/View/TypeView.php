<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;

class TypeView extends Tracker_Artifact_View_View
{
    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return dgettext('tuleap-tracker', 'Children');
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return 'link';
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        $layout = $GLOBALS['HTML'];
        \assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->addJavascriptAsset(new JavascriptAsset(
            new IncludeAssets(__DIR__ . '/../../../../frontend-assets', '/assets/trackers'),
            "children-view.js",
        ));

        return '<div data-artifact-id="' . $this->artifact->getId() . '" class="artifact-type"></div>';
    }
}
