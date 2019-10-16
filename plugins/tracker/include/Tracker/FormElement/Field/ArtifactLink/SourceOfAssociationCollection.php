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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact;
use PFUser;

class SourceOfAssociationCollection implements \Countable
{
    /**
     * @var Tracker_Artifact[]
     */
    private $artifacts = array();

    public function add(Tracker_Artifact $artifact)
    {
        $this->artifacts[] = $artifact;
    }

    public function linkToArtifact(Tracker_Artifact $artifact, PFUser $submitter)
    {
        foreach ($this->artifacts as $source_artifact) {
            $source_artifact->linkArtifact($artifact->getId(), $submitter);
        }
    }

    public function count()
    {
        return count($this->artifacts);
    }
}
