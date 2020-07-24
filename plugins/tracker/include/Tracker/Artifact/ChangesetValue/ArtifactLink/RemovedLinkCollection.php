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

/**
 * I collect links of artifact that have been removed between two changesets
 */
class RemovedLinkCollection implements ICollectChangeOfLinksBetweenTwoChangesets
{
    /**
     * @var CollectionOfLinksFormatter
     */
    private $formatter;

    /**
     * @var Tracker_ArtifactLinkInfo[]
     */
    private $removed = [];

    public function __construct(CollectionOfLinksFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function add(Tracker_ArtifactLinkInfo $artifactlinkinfo)
    {
        $this->removed[] = $artifactlinkinfo;
    }

    /**
     * @return string
     */
    public function fetchFormatted(PFUser $user, $format, $ignore_perms)
    {
        if (! $this->removed) {
            return '';
        }

        return sprintf(dgettext('tuleap-tracker', 'Removed: %s'), $this->formatter->format($this->removed, $user, $format, $ignore_perms));
    }
}
