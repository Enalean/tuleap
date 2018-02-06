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

namespace Tuleap\Tracker\Report\AdditionalCriteria;

use Tracker_Report;
use Tracker_Report_AdditionalCriterion;

class CommentCriterionValueSaver
{
    /**
     * @var CommentDao
     */
    private $dao;

    public function __construct(CommentDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveValueForReport(Tracker_Report $report, Tracker_Report_AdditionalCriterion $comment_criterion)
    {
        if ($comment_criterion->getValue() != '') {
            $this->dao->save($report->getId(), $comment_criterion->getValue());
        } else {
            $this->dao->delete($report->getId());
        }
    }
}
