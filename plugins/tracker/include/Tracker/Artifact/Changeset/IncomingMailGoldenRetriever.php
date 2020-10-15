<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_Artifact_Changeset_IncomingMailGoldenRetriever
{

    /** @var Tracker_Artifact_Changeset_IncomingMailDao */
    private $dao;

    /** @var array */
    private $cache = [];

    /** @var Tracker_Artifact_Changeset_IncomingMailGoldenRetriever */
    private static $instance;

    public function __construct(Tracker_Artifact_Changeset_IncomingMailDao $dao)
    {
        $this->dao = $dao;
    }

    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c(
                new Tracker_Artifact_Changeset_IncomingMailDao()
            );
        }
        return self::$instance;
    }

    /** @return string | null */
    public function getRawMailThatCreatedArtifact(Artifact $artifact)
    {
        return $this->getRawMailForChangeset($artifact->getFirstChangeset());
    }

    /** @return string | null */
    public function getRawMailThatCreatedChangeset(Tracker_Artifact_Changeset $changeset)
    {
        return $this->getRawMailForChangeset($changeset);
    }

    private function getRawMailForChangeset(Tracker_Artifact_Changeset $changeset)
    {
        $raw_mails = $this->getCachedRawMailByChangesetsForArtifact($changeset->getArtifact());

        $changeset_id = $changeset->getId();
        if (isset($raw_mails[$changeset_id])) {
            return $raw_mails[$changeset_id];
        }

        return null;
    }

    private function getCachedRawMailByChangesetsForArtifact(Artifact $artifact)
    {
        if (! isset($this->cache[$artifact->getId()])) {
            $this->cache[$artifact->getId()] = [];
            foreach ($this->dao->searchByArtifactId($artifact->getId()) as $row) {
                $this->cache[$artifact->getId()][$row['changeset_id']] = $row['raw_mail'];
            }
        }

        return $this->cache[$artifact->getId()];
    }
}
