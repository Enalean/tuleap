<?php
/*
 * Copyright (C) Enalean SAS, 2016. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tracker_ArtifactLinkInfo;
use PFUser;

class CollectionOfLinksFormatter
{

    /**
     * @param Tracker_ArtifactLinkInfo[] $list_of_artifactlinkinfo
     * @param string $format
     *
     * @return string
     */
    public function format(array $list_of_artifactlinkinfo, PFUser $user, $format, $ignore_perms)
    {
        $formatted_links_user_can_see = array();

        foreach ($list_of_artifactlinkinfo as $link) {
            if (! $link->userCanView($user) && ! $ignore_perms) {
                continue;
            }

            if ($format === 'html') {
                $formatted_links_user_can_see[] = $link->getLink();
            } else {
                $formatted_links_user_can_see[] = $link->getLabel();
            }
        }

        return implode(', ', $formatted_links_user_can_see);
    }
}
