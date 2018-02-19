<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic;

use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

class SemanticUsageChecker
{
    /**
     * @var bool[]
     */
    private $cache_already_checked;
    /**
     * @var Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var Tracker_Semantic_StatusDao
     */
    private $status_dao;

    public function __construct(
        Tracker_Semantic_TitleDao $title_dao,
        Tracker_Semantic_DescriptionDao $description_dao,
        Tracker_Semantic_StatusDao $status_dao
    ) {
        $this->title_dao       = $title_dao;
        $this->description_dao = $description_dao;
        $this->status_dao      = $status_dao;

        $this->cache_already_checked = array();
    }

    /**
     * @param Metadata $metadata
     * @param int[] $trackers_id
     * @throws DescriptionIsMissingInAtLeastOneTrackerException
     * @throws TitleIsMissingInAtLeastOneTrackerException
     * @throws StatusIsMissingInAtLeastOneTrackerException
     */
    public function checkSemanticIsUsedByAllTrackers(Metadata $metadata, array $trackers_id)
    {
        if (isset($this->cache_already_checked[$metadata->getName()])) {
            return;
        }
        $this->cache_already_checked[$metadata->getName()] = true;

        switch ($metadata->getName()) {
            case AllowedMetadata::TITLE:
                $this->checkTitleIsUsedByAllTrackers($trackers_id);
                break;
            case AllowedMetadata::DESCRIPTION:
                $this->checkDescriptionIsUsedByAllTrackers($trackers_id);
                break;
            case AllowedMetadata::STATUS:
                $this->checkStatusIsUsedByAllTrackers($trackers_id);
                break;
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws TitleIsMissingInAtLeastOneTrackerException
     */
    private function checkTitleIsUsedByAllTrackers(array $trackers_id)
    {
        $count = $this->title_dao->getNbOfTrackerWithoutSemanticTitleDefined($trackers_id);
        if ($count > 0) {
            throw new TitleIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws DescriptionIsMissingInAtLeastOneTrackerException
     */
    private function checkDescriptionIsUsedByAllTrackers(array $trackers_id)
    {
        $count = $this->description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($trackers_id);
        if ($count > 0) {
            throw new DescriptionIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws StatusIsMissingInAtLeastOneTrackerException
     */
    private function checkStatusIsUsedByAllTrackers(array $trackers_id)
    {
        $count = $this->status_dao->getNbOfTrackerWithoutSemanticStatusDefined($trackers_id);
        if ($count > 0) {
            throw new StatusIsMissingInAtLeastOneTrackerException($count);
        }
    }
}
