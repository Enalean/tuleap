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
 * I collect links of artifact that have changed their nature between two changesets
 */
class UpdatedNatureLinkCollection implements ICollectChangeOfLinksBetweenTwoChangesets
{

    /**
     * @var NaturePresenter
     */
    private $target_nature;

    /**
     * @var NaturePresenter
     */
    private $source_nature;

    /**
     * @var CollectionOfLinksFormatter
     */
    private $formatter;

    /**
     * @var Tracker_ArtifactLinkInfo[]
     */
    private $changed = [];

    public function __construct(
        NaturePresenter $source_nature,
        NaturePresenter $target_nature,
        CollectionOfLinksFormatter $formatter
    ) {
        $this->source_nature = $source_nature;
        $this->target_nature = $target_nature;
        $this->formatter     = $formatter;
    }

    public function add(Tracker_ArtifactLinkInfo $artifactlinkinfo)
    {
        $this->changed[] = $artifactlinkinfo;
    }

    /**
     * @return string
     */
    public function fetchFormatted(PFUser $user, $format, $ignore_perms)
    {
        $source = $this->source_nature->forward_label;
        if (! $source) {
            $source = dgettext('tuleap-tracker', 'no type');
        }

        $target = $this->target_nature->forward_label;
        if (! $target) {
            $target = dgettext('tuleap-tracker', 'no type');
        }

        return sprintf(dgettext('tuleap-tracker', 'Changed type from %s to %s: %s'), $source, $target, $this->formatter->format($this->changed, $user, $format, $ignore_perms));
    }
}
