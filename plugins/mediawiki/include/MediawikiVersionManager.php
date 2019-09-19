<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class MediawikiVersionManager
{

    public const MEDIAWIKI_120_VERSION = "1.20";
    public const MEDIAWIKI_123_VERSION = "1.23";

    public static $AVAILABLE_VERSIONS = array(
        self::MEDIAWIKI_120_VERSION,
        self::MEDIAWIKI_123_VERSION
    );

    /** @var MediawikiVersionDao */
    private $version_dao;

    public function __construct(MediawikiVersionDao $version_dao)
    {
        $this->version_dao = $version_dao;
    }

    public function saveVersionForProject(Project $project, $version)
    {
        if (! in_array($version, self::$AVAILABLE_VERSIONS)) {
            throw new Mediawiki_UnsupportedVersionException();
        }

        return $this->version_dao->saveMediawikiVersionForProject($project->getID(), $version);
    }

    public function getVersionForProject(Project $project)
    {
        $row = $this->version_dao->getVersionForProject($project->getID());

        if (! $row) {
            return;
        }

        return $row['mw_version'];
    }

    public function countProjectsToMigrateTo123()
    {
        return $this->version_dao->countMediawikiToMigrate(self::MEDIAWIKI_120_VERSION);
    }

    public function getAllProjectsToMigrateTo123()
    {
        $project_ids = array();
        $dar = $this->version_dao->getAllMediawikiToMigrate(self::MEDIAWIKI_120_VERSION);
        foreach ($dar as $row) {
            $project_ids[] = $row['group_id'];
        }
        return $project_ids;
    }
}
