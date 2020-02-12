<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Codendi_Request;
use PFUser;
use Tracker_Artifact_View_View;
use Tracker_Artifact;

class ArtifactView extends Tracker_Artifact_View_View
{
    /**
     * @var Tracker_Artifact
     */
    private $release_id;

    public function __construct($release_id, Tracker_Artifact $artifact, Codendi_Request $request, PFUser $user)
    {
        parent::__construct($artifact, $request, $user);

        $this->release_id = $release_id;
    }

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return dgettext('tuleap-frs', 'File release')
        . ' <i class="fa fa-external-link"></i>';
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return "frs";
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        // Nothing to fetch as the tab is a redirect to the frs
    }

    public function getURL()
    {
        $release_id = urlencode((string) $this->release_id);
        return "/frs/release/$release_id/release-notes";
    }
}
