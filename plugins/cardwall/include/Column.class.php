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
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
     */
    private $field_provider;
    
    /**
     * @param int    $id
     * @param string $label
     */
    public function __construct($id, $label, $bgcolor, $fgcolor, 
                                Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
                                $config) {
        $this->id      = $id;
        $this->label   = $label;
        $this->bgcolor = $bgcolor;
        $this->fgcolor = $fgcolor;
        $this->field_provider = $field_provider;
        $this->config = $config;
    }
    
    public function isInColumn(Tracker_Artifact $artifact) {
        $field           = $this->field_provider->getField($artifact);
        $artifact_status = null;
        if ($field) {
            $artifact_status = $field->getFirstValueFor($artifact->getLastChangeset());
        }

        
        return $this->config->isMappedTo($artifact->getTrackerId(), $artifact_status, $this) || 
               $this->isMatchForThisColumn($artifact_status);
    }
    
    private function isMatchForThisColumn($artifact_status) {
        return $this->matchesLabel($artifact_status) || $this->matchesTheNoneColumn($artifact_status);
    }

    private function matchesLabel($artifact_status) {
        return $artifact_status === $this->label;
    }

    private function matchesTheNoneColumn($artifact_status) {
        return $artifact_status === null && $this->id == 100;
    }


}
?>
