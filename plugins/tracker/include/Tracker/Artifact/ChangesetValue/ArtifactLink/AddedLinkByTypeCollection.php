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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tracker_ArtifactLinkInfo;
use PFUser;

/**
 * I collect links of artifact that have been added with a given type between two changesets
 */
class AddedLinkByTypeCollection implements ICollectChangeOfLinksBetweenTwoChangesets
{
    /**
     * @var CollectionOfLinksFormatter
     */
    private $formatter;

    /**
     * @var TypePresenter
     */
    private $type;

    /**
     * @var Tracker_ArtifactLinkInfo[]
     */
    private $added = [];

    public function __construct(TypePresenter $type, CollectionOfLinksFormatter $formatter)
    {
        $this->type      = $type;
        $this->formatter = $formatter;
    }

    #[\Override]
    public function add(Tracker_ArtifactLinkInfo $artifactlinkinfo)
    {
        $this->added[] = $artifactlinkinfo;
    }

    #[\Override]
    public function fetchFormatted(PFUser $user, $format, $ignore_perms): string
    {
        if ($this->type->shortname) {
            return sprintf(dgettext('tuleap-tracker', 'Added %s: %s'), $this->type->forward_label, $this->formatter->format($this->added, $user, $format, $ignore_perms));
        }
        return sprintf(dgettext('tuleap-tracker', 'Added: %s'), $this->formatter->format($this->added, $user, $format, $ignore_perms));
    }
}
