<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;

class SemanticTimeframeBuilder
{
    /**
     * @var SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(SemanticTimeframeDao $dao, \Tracker_FormElementFactory $form_element_factory)
    {
        $this->dao = $dao;
        $this->form_element_factory = $form_element_factory;
    }

    public function getSemantic(Tracker $tracker): SemanticTimeframe
    {
        $row = $this->dao->searchByTrackerId((int) $tracker->getId());
        if ($row === null) {
            return new SemanticTimeframe($tracker, null, null, null);
        }

        $start_date_field = $this->form_element_factory->getUsedDateFieldById(
            $tracker,
            (int) $row['start_date_field_id']
        );

        $duration_field = null;
        if ($row['duration_field_id'] !== null) {
            $duration_field = $this->form_element_factory->getUsedFieldByIdAndType(
                $tracker,
                (int) $row['duration_field_id'],
                ['int', 'float', 'computed']
            );
            assert($duration_field === null || $duration_field instanceof Tracker_FormElement_Field_Numeric);
        }

        $end_date_field = null;
        if ($row['end_date_field_id'] !== null) {
            $end_date_field = $this->form_element_factory->getUsedDateFieldById(
                $tracker,
                (int) $row['end_date_field_id']
            );
            assert($end_date_field === null || $end_date_field instanceof Tracker_FormElement_Field_Date);
        }

        return new SemanticTimeframe($tracker, $start_date_field, $duration_field, $end_date_field);
    }
}
