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

namespace Tuleap\TextualReport;

use Tracker;
use Tracker_Report;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;

class DocumentCanBeDownloadedChecker
{
    public function hasMatchingArtifacts(Tracker_Report $report)
    {
        $matching_ids = $report->getMatchingIds();

        return isset($matching_ids['id']) && strlen($matching_ids['id']) > 0;
    }

    public function hasNeededSemantics(Tracker $tracker)
    {
        return Tracker_Semantic_Title::load($tracker)->getField()
            && Tracker_Semantic_Description::load($tracker)->getField();
    }
}
