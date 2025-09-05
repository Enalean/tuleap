<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Criteria;

use Tuleap\DB\DataAccessObject;

final class CriteriaDateValueDAO extends DataAccessObject implements DeleteReportCriteriaValue
{
    #[\Override]
    public function deleteCriteriaFieldValue(\Tracker_Report_Criteria $criteria): void
    {
        $this->getDB()->run(
            'DELETE tracker_report_criteria_date_value.*
             FROM tracker_report_criteria_date_value
             JOIN tracker_report_criteria ON criteria_id = id
             WHERE report_id = ? AND field_id = ?',
            $criteria->getReport()->id,
            $criteria->field->getId()
        );
    }
}
