<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * Build the artifact tree to be presented on the cardwall
 */
class Cardwall_PaneBoardBuilder
{
    private $artifact_factory;
    private $presenter_builder;
    private $dao;
    private $swimline_factory;

    public function __construct(Cardwall_CardInCellPresenterBuilder $presenter_builder, Tracker_ArtifactFactory $artifact_factory, BacklogItemDao $dao, Cardwall_SwimlineFactory $swimline_factory)
    {
        $this->presenter_builder = $presenter_builder;
        $this->artifact_factory  = $artifact_factory;
        $this->dao               = $dao;
        $this->swimline_factory  = $swimline_factory;
    }

    /**
     * Get the board
     *
     * @return \Cardwall_Board
     */
    public function getBoard(PFUser $user, Artifact $milestone_artifact, ColumnCollection $columns, Cardwall_MappingCollection $mapping_collection)
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
    private function getSwimlines(PFUser $user, Artifact $milestone_artifact, ColumnCollection $columns)
    {
        $swimlines = [];
        foreach ($this->dao->getBacklogArtifacts($milestone_artifact->getId()) as $row) {
            $swimline_artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($swimline_artifact->userCanView($user)) {
                $swimlines[] = $this->buildSwimlineForArtifact($user, $swimline_artifact, $columns);
            }
        }
        return $swimlines;
    }

    private function buildSwimlineForArtifact(PFUser $user, Artifact $artifact, ColumnCollection $columns)
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

    private function buildSwimlineSolo(Artifact $artifact, Cardwall_CardInCellPresenter $artifact_presenter, ColumnCollection $columns)
    {
        $cells = $this->swimline_factory->getCells($columns, [$artifact_presenter]);

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

    private function buildSwimline(Cardwall_CardInCellPresenter $artifact_presenter, ColumnCollection $columns, array $children_presenters)
    {
        return new Cardwall_Swimline(
            $artifact_presenter,
            $this->swimline_factory->getCells($columns, $children_presenters)
        );
    }

    private function buildSwimlineSoloNoMatchingColumns(Cardwall_CardInCellPresenter $artifact_presenter, Artifact $artifact, array $cells)
    {
        return new Cardwall_SwimlineSoloNoMatchingColumns(
            $artifact_presenter,
            $artifact,
            $cells
        );
    }
}
