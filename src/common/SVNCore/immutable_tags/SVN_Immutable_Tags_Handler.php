<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

class SVN_Immutable_Tags_Handler // phpcs:ignore
{
    /** @var SVN_Immutable_Tags_DAO */
    private $dao;

    public function __construct(SVN_Immutable_Tags_DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return string
     */
    public function getImmutableTagsWhitelistForProject($project_id)
    {
        $result = $this->dao->getImmutableTagsWhitelistForProject($project_id)->getRow();

        if (! $result) {
            return '';
        }

        return $result['whitelist'];
    }

    /**
     * @return string
     */
    public function getImmutableTagsPathForProject($project_id)
    {
        $result = $this->dao->getImmutableTagsPathForProject($project_id)->getRow();

        if (! $result) {
            return '';
        }

        return $result['paths'];
    }

    public function getAllowedTagsFromWhiteList(Project $project)
    {
        $content = $this->getImmutableTagsWhitelistForProject($project->getID());

        if (! $content) {
            return [];
        }

        return explode(PHP_EOL, $content);
    }

    public function doesProjectUsesImmutableTags(Project $project)
    {
        return $this->getImmutableTagsPathForProject($project->getID()) != '';
    }
}
