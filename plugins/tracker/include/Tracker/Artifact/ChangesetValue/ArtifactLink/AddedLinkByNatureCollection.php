<?php
/**
 * Copyright (C) Enalean SAS, 2016 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tracker_ArtifactLinkInfo;
use PFUser;

/**
 * I collect links of artifact that have been added with a given nature between two changesets
 */
class AddedLinkByNatureCollection implements ICollectChangeOfLinksBetweenTwoChangesets
{
    /**
     * @var CollectionOfLinksFormatter
     */
    private $formatter;

    /**
     * @var NaturePresenter
     */
    private $nature;

    /**
     * @var Tracker_ArtifactLinkInfo[]
     */
    private $added = array();

    public function __construct(NaturePresenter $nature, CollectionOfLinksFormatter $formatter)
    {
        $this->nature    = $nature;
        $this->formatter = $formatter;
    }

    public function add(Tracker_ArtifactLinkInfo $artifactlinkinfo)
    {
        $this->added[] = $artifactlinkinfo;
    }

    public function fetchFormatted(PFUser $user, $format, $ignore_perms): string
    {
        if ($this->nature->shortname) {
            return sprintf(dgettext('tuleap-tracker', 'Added %s: %s'), $this->nature->forward_label, $this->formatter->format($this->added, $user, $format, $ignore_perms));
        }
        return sprintf(dgettext('tuleap-tracker', 'Added: %s'), $this->formatter->format($this->added, $user, $format, $ignore_perms));
    }
}
