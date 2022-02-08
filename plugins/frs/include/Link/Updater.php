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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS\Link;

class Updater
{
    /**
     * @var Retriever
     */
    private $retriever;

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao, Retriever $retriever)
    {
        $this->dao       = $dao;
        $this->retriever = $retriever;
    }

    public function updateLink($release_id, $artifact_id)
    {
        if (! $this->doesLinkedArtifactIdChanged($release_id, $artifact_id)) {
            return true;
        }

        if ($artifact_id) {
            return $this->dao->saveLink($release_id, $artifact_id);
        } else {
            return $this->dao->deleteLink($release_id);
        }
    }

    private function doesLinkedArtifactIdChanged($release_id, $artifact_id)
    {
        $previous_linked_artifact = $this->retriever->getLinkedArtifactId($release_id);

        return $previous_linked_artifact !== $artifact_id;
    }
}
