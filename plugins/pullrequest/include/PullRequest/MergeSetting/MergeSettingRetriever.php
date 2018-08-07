<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\MergeSetting;

use Project;

class MergeSettingRetriever
{
    /**
     * @var MergeSettingDAO
     */
    private $dao;

    public function __construct(MergeSettingDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return MergeSetting
     */
    public function getMergeSettingForRepository(\GitRepository $repository)
    {
        return $this->instantiateMergeSetting($this->dao->getMergeSettingByRepositoryID($repository->getId()));
    }

    public function getMergeSettingForProject(Project $project)
    {
        return $this->instantiateMergeSetting($this->dao->getMergeSettingByProjectID($project->getId()));
    }

    /**
     * @param $merge_setting
     *
     * @return MergeSettingDefault|MergeSettingWithValue
     */
    private function instantiateMergeSetting($merge_setting)
    {
        if ($merge_setting === null) {
            return new MergeSettingDefault();
        }

        return new MergeSettingWithValue($merge_setting['merge_commit_allowed']);
    }
}
