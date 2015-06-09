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

class SVN_Immutable_Tags_Handler {

    /** @var SVN_Immutable_Tags_DAO */
    private $dao;

    public function __construct(SVN_immutable_tags_DAO $dao) {
        $this->dao = $dao;
    }

    /**
     * @return string
     */
    public function getImmutableTagsWhitelistForProject($project_id) {
        $result = $this->dao->getImmutableTagsWhitelistForProject($project_id)->getRow();

        if (! $result) {
            return '';
        }

        return $result['content'];
    }

    public function saveWhitelistForProject($project_id, $whitelist) {
        return $this->dao->saveWhitelistForProject($project_id, $whitelist);
    }

    public function getAllowedTagsFromWhiteList(Project $project) {
        $content = $this->getImmutableTagsWhitelistForProject($project->getID());

        if (! $content) {
            return array();
        }

        return explode(PHP_EOL, $content);
    }

}