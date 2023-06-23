<?php
/**
 * Copyright (c) Enalean 2011 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

class Tracker_Report_Criteria
{
    public $id;
    public $report;
    public $field;
    public $rank;

    /**
     * @var bool|int
     */
    public $is_advanced;

    /**
     * Constructor
     *
     * WARNING: cannot add typehinting on parameters as sometimes they might be
     * null (and it's crappy to have null args in the middle of arg list).
     *
     * @param int $id the id of the criteria
     * @param Tracker_Report $report the id of the report
     * @param Tracker_FormElement_Field $field the name of the renderer
     * @param int $rank the rank
     * @param int | bool $is_advanced use advanced search for this field
     */
    public function __construct($id, $report, $field, $rank, $is_advanced)
    {
        $this->id          = $id;
        $this->report      = $report;
        $this->field       = $field;
        $this->rank        = $rank;
        $this->is_advanced = $is_advanced;
    }

    /**
     * @param bool $is_advanced
     */
    public function setIsAdvanced($is_advanced)
    {
        $this->is_advanced = $is_advanced;
    }

    /**
     * @return Option<ParametrizedFromWhere>
     */
    public function getFromWhere(): Option
    {
        return $this->field->getCriteriaFromWhere($this);
    }

    public function fetch()
    {
        return $this->field->fetchCriteria($this);
    }

    public function fetchWithoutExpandFunctionnality()
    {
        return $this->field->fetchCriteriaWithoutExpandFunctionnality($this);
    }

    public function delete()
    {
        return $this->field->deleteCriteriaValue($this);
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $value
     */
    public function updateValue($value)
    {
        $this->field->updateCriteriaValue($this, $value);
    }

    /**
     * Transforms Criteria into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the Criteria is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $root->addAttribute('rank', $this->rank);
        if ($this->is_advanced) {
            $root->addAttribute('is_advanced', (string) $this->is_advanced);
        }

        $root->addChild('field')->addAttribute('REF', array_search($this->field->id, $xmlMapping));
        $this->field->exportCriteriaValueToXML($this, $root);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getReport(): Tracker_Report
    {
        return $this->report;
    }
}
