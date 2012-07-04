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

require_once 'Swimline.class.php';

/**
 * Build swimlines for the dashboard
 */
class Cardwall_SwimlineFactory {

    /**
     * @return array of Cardwall_Swimline
     */
    public function getSwimlines(array $columns, array $nodes) {
        $swimlines = array();
        foreach ($nodes as $child) {
            $potential_presenters = $this->extractPotentialPresentersFrom($child->getChildren());
            $cells = $this->getCellsOfSwimline($columns, $potential_presenters);
            $swimlines[] = new Cardwall_Swimline($child, $cells);
        }
        return $swimlines;
    }

    private function extractPotentialPresentersFrom(array $nodes) {
        $potential_presenters = array();
        foreach ($nodes as $node) {
            $potential_presenters[] = $node->getCardInCellPresenter();
        }
        return $potential_presenters;
    }

    public function getCellsOfSwimline(array $columns, array $potential_presenters) {
        $cells = array();
        foreach ($columns as $column) {
            $cells[] = $this->getCell($column, $potential_presenters);
        }
        return $cells;
    }

    private function getCell(Cardwall_Column $column, array $potential_presenters) {
        $presenters = array();
        foreach ($potential_presenters as $p) {
            $this->addNodeToCell($p, $column, $presenters);
        }
        return array('cardincell_presenters' => $presenters);;
    }

    private function addNodeToCell(Cardwall_CardInCellPresenter $presenter, Cardwall_Column $column, array &$presenters) {
        $artifact        = $presenter->getArtifact();
        if ($this->isArtifactInCell($artifact, $column)) {
            $presenters[] = $presenter;
        }
    }

    private function isArtifactInCell(Tracker_Artifact $artifact, Cardwall_Column $column) {
        $artifact_status = $artifact->getStatus();
        return $artifact_status === $column->label || $artifact_status === null && $column->id == 100;
    }

}
?>
