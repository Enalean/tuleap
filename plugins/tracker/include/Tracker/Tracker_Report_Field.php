<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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


interface Tracker_Report_Field extends Tracker_FormElement_IHaveAnId, Tracker_FormElement_Usable //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * Return a label (e.g. usable both in a form or as a column header)
     */
    public function getLabel();

    /**
     * Display the field as a criteria
     * @return string
     */
    public function fetchCriteria(Tracker_Report_Criteria $criteria);

    /**
     * Display the field as criteria but without been able to be switch to advanced mode
     * @return string
     */
    public function fetchCriteriaWithoutExpandFunctionnality(Tracker_Report_Criteria $criteria);

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?array $redirection_parameters = null,
    ): string;
}
