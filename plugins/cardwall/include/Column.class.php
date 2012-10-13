<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * A column in a cardwall
 */
class Cardwall_Column {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $bgcolor;

    /**
     * @var string
     */
    public $fgcolor;

    /**
     * @param int    $id
     * @param string $label
     */
    public function __construct($id, $label, $bgcolor, $fgcolor) {
        $this->id      = $id;
        $this->label   = $label;
        $this->bgcolor = $bgcolor;
        $this->fgcolor = $fgcolor;
    }

    /**
     * Return true if the given status can belong to current column
     *
     * @param String                               $artifact_status
     * @param Cardwall_OnTop_Config_TrackerMapping $tracker_mapping
     *
     * @return Boolean
     */
    public function canContainStatus($artifact_status, Cardwall_OnTop_Config_TrackerMapping $tracker_mapping = null) {
        $is_mapped = false;
        if ($tracker_mapping) {
            $is_mapped = $tracker_mapping->isMappedTo($this, $artifact_status);
        }
        return $is_mapped || $this->matchesStatus($artifact_status);
    }

    private function matchesStatus($artifact_status) {
        return $this->matchesLabel($artifact_status) || $this->matchesTheNoneColumn($artifact_status);
    }

    private function matchesLabel($artifact_status) {
        return $artifact_status === $this->label;
    }

    private function matchesTheNoneColumn($artifact_status) {
        return $artifact_status === null && $this->id == 100;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getBgcolor() {
        return $this->bgcolor;
    }

    /**
     * @return string
     */
    public function getFgcolor() {
        return $this->fgcolor;
    }

}
?>
