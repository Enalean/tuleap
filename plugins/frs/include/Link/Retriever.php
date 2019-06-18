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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS\Link;

use Tracker_Artifact;

class Retriever
{

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    public function getLinkedArtifactId($release_id)
    {
        $row = $this->dao->searchLinkedArtifactForRelease($release_id);

        if ($row && $row['artifact_id'] !== null) {
            return $row['artifact_id'];
        }

        return null;
    }

    public function getLinkedReleaseId(Tracker_Artifact $artifact)
    {
        $row = $this->dao->searchLinkedReleaseForArtifact($artifact->getId());

        if ($row && $row['release_id'] !== null) {
            return $row['release_id'];
        }

        return null;
    }
}
