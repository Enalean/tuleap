<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Tracker;
use Tracker_FormElement_Field_List_Value;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;

class SemanticDoneLoader
{
    /**
     * @var SemanticDoneDao
     */
    private $dao;
    /**
     * @var SemanticDoneValueChecker
     */
    private $value_checker;

    public function __construct(SemanticDoneDao $dao, SemanticDoneValueChecker $value_checker)
    {
        $this->dao           = $dao;
        $this->value_checker = $value_checker;
    }

    public function load(Tracker $tracker, Tracker_Semantic_Status $semantic_status): SemanticDone
    {
        $semantic_status_field = $semantic_status->getField();
        $done_values           = [];

        if ($semantic_status_field) {
            foreach ($this->dao->getSelectedValues($tracker->getId()) as $selected_value_row) {
                $value_id = $selected_value_row['value_id'];
                try {
                    $value = $semantic_status_field->getBind()->getValue($value_id);
                } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
                    continue;
                }

                assert($value === null || $value instanceof Tracker_FormElement_Field_List_Value);
                if ($value && $this->value_checker->isValueAPossibleDoneValue($value, $semantic_status)) {
                    $done_values[$value_id] = $value;
                }
            }
        }

        return new SemanticDone($tracker, $semantic_status, $this->dao, $this->value_checker, $done_values);
    }
}
