<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * Build the artifact tree to be presented on the cardwall
 */
class Cardwall_PaneBoardBuilder
{

    private $artifact_factory;
    private $presenter_builder;
    private $dao;
    private $swimline_factory;

    public function __construct(Cardwall_CardInCellPresenterBuilder $presenter_builder, Tracker_ArtifactFactory $artifact_factory, AgileDashboard_BacklogItemDao $dao, Cardwall_SwimlineFactory $swimline_factory)
    {
        $this->presenter_builder = $presenter_builder;
        $this->artifact_factory = $artifact_factory;
        $this->dao = $dao;
        $this->swimline_factory = $swimline_factory;
    }

    /**
     * Get the board
     *
     * @return \Cardwall_Board
     */
    public function getBoard(PFUser $user, Tracker_Artifact $milestone_artifact, Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_MappingCollection $mapping_collection)
    {
        return new Cardwall_Board(
            $this->getSwimlines($user, $milestone_artifact, $columns),
            $columns,
            $mapping_collection
        );
    }

    /**
     * Retrieves the artifacts planned for the given milestone artifact.
     *
     *
     * @return Cardwall_Swimline[]
     */
    private function getSwimlines(PFUser $user, Tracker_Artifact $milestone_artifact, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $swimlines = array();
        foreach ($this->dao->getBacklogArtifacts($milestone_artifact->getId()) as $row) {
            $swimline_artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($swimline_artifact->userCanView($user)) {
                $swimlines[] = $this->buildSwimlineForArtifact($user, $swimline_artifact, $columns);
            }
        }
        return $swimlines;
    }

    private function buildSwimlineForArtifact(PFUser $user, Tracker_Artifact $artifact, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $artifact_presenter = $this->presenter_builder->getCardInCellPresenter($artifact, $artifact->getId());
        $children           = $artifact->getChildrenForUser($user);

        if ($children) {
            $children_presenters = $this->presenter_builder->getCardInCellPresenters($children, $artifact->getId());
            return $this->buildSwimline($artifact_presenter, $columns, $children_presenters);
        } else {
            return $this->buildSwimlineSolo($artifact, $artifact_presenter, $columns);
        }
    }

    private function buildSwimlineSolo(Tracker_Artifact $artifact, Cardwall_CardInCellPresenter $artifact_presenter, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $cells = $this->swimline_factory->getCells($columns, array($artifact_presenter));

        if ($this->areSwimlineCellsEmpty($cells)) {
            return $this->buildSwimlineSoloNoMatchingColumns($artifact_presenter, $artifact, $cells);
        }

        return new Cardwall_SwimlineSolo(
            $artifact->getId(),
            $cells
        );
    }

    private function areSwimlineCellsEmpty(array $cells)
    {
        foreach ($cells as $cell) {
            if ($cell['cardincell_presenters']) {
                return false;
            }
        }

        return true;
    }

    private function buildSwimline(Cardwall_CardInCellPresenter $artifact_presenter, Cardwall_OnTop_Config_ColumnCollection $columns, array $children_presenters)
    {
        return new Cardwall_Swimline(
            $artifact_presenter,
            $this->swimline_factory->getCells($columns, $children_presenters)
        );
    }

    private function buildSwimlineSoloNoMatchingColumns(Cardwall_CardInCellPresenter $artifact_presenter, Tracker_Artifact $artifact, array $cells)
    {
        return new Cardwall_SwimlineSoloNoMatchingColumns(
            $artifact_presenter,
            $artifact,
            $cells
        );
    }
}
