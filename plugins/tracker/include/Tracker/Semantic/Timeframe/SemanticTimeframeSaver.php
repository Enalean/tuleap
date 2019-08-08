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

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;

class SemanticTimeframeSaver
{
    /**
     * @var SemanticTimeframeDao
     */
    private $dao;

    public function __construct(SemanticTimeframeDao $dao)
    {
        $this->dao = $dao;
    }

    public function save(SemanticTimeframe $semantic_timeframe): bool
    {
        $start_date_field = $semantic_timeframe->getStartDateField();
        $duration_field   = $semantic_timeframe->getDurationField();
        $end_date_field   = $semantic_timeframe->getEndDateField();

        if ($start_date_field === null || ($duration_field === null && $end_date_field === null)) {
            return false;
        }

        return $this->dao->save(
            (int) $semantic_timeframe->getTracker()->getId(),
            (int) $start_date_field->getId(),
            $duration_field ? (int) $duration_field->getId() : null,
            $end_date_field ? (int) $end_date_field->getId() : null
        );
    }
}
