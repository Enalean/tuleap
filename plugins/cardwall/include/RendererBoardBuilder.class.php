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

class Cardwall_RendererBoardBuilder
{

    /** @var Cardwall_CardInCellPresenterBuilder */
    private $presenter_builder;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    private $swimline_factory;

    public function __construct(Cardwall_CardInCellPresenterBuilder $presenter_builder, Tracker_ArtifactFactory $artifact_factory, Cardwall_SwimlineFactory $swimline_factory)
    {
        $this->presenter_builder = $presenter_builder;
        $this->artifact_factory  = $artifact_factory;
        $this->swimline_factory  = $swimline_factory;
    }

    /**
     * Get the board
     *
     * @param array $artifact_ids
     * @param Cardwall_MappingCollection $mappings_collection
     * @return \Cardwall_Board
     */
    public function getBoard(array $artifact_ids, Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_MappingCollection $mapping_collection)
    {
        return new Cardwall_Board($this->getSwimlines($artifact_ids, $columns), $columns, $mapping_collection);
    }

    private function getSwimlines(array $artifact_ids, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        return array(new Cardwall_SwimlineTrackerRenderer($this->swimline_factory->getCells(
            $columns,
            $this->getCardsPresenters($artifact_ids)
        )));
    }

    protected function getCardsPresenters(array $artifact_ids)
    {
        $cards = array();
        foreach ($artifact_ids as $id) {
            $artifact = $this->artifact_factory->getArtifactById($id);
            $cards[]  = $this->presenter_builder->getCardInCellPresenter($artifact, Cardwall_SwimlineTrackerRenderer::FAKE_SWIMLINE_ID_FOR_TRACKER_RENDERER);
        }
        return $cards;
    }
}
