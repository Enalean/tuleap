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
    public function __construct($id, $label, $bgcolor, $fgcolor, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider = null) {
        $this->id      = $id;
        $this->label   = $label;
        $this->bgcolor = $bgcolor;
        $this->fgcolor = $fgcolor;
        $this->field_provider = $field_provider;
    }
    
    public function isArtifactInCell2(Tracker_Artifact $artifact) {
        $artifact_status = $this->field_provider->getField($artifact)->getValueFor($artifact->getLastChangeset());
        return $artifact_status === $this->label
                || $artifact_status === null && $this->id == 100;
    }

}
?>
