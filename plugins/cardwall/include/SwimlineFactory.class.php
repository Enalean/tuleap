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
require_once 'OnTop/Config.class.php';
require_once 'FieldProviders/IProvideFieldGivenAnArtifact.class.php';

/**
 * Build swimlines for the dashboard
 */
class Cardwall_SwimlineFactory
{

    /** @var Cardwall_OnTop_Config */
    private $config;

    /** @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact */
    private $field_provider;

    public function __construct(Cardwall_OnTop_Config $config, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider)
    {
        $this->config = $config;
        $this->field_provider = $field_provider;
    }

    /**
     * public for testing
     *
     * @param array of Cardwall_Column $columns
     * @param array of Cardwall_CardInCellPresenter $potential_presenters
     * @return array
     */
    public function getCells(Cardwall_OnTop_Config_ColumnCollection $columns, array $potential_presenters)
    {
        $cells = [];
        foreach ($columns as $column) {
            $cells[] = $this->getCell($column, $potential_presenters);
        }
        return $cells;
    }

    private function getCell(Cardwall_Column $column, array $potential_presenters)
    {
        $retained_presenters = [];
        foreach ($potential_presenters as $p) {
            $this->addNodeToCell($p, $column, $retained_presenters);
        }

        return [
            'column_id' => $column->getId(),
            'column_stacked' => $column->isAutostacked(),
            'cardincell_presenters' => $retained_presenters
        ];
    }

    private function addNodeToCell(Cardwall_CardInCellPresenter $presenter, Cardwall_Column $column, array &$presenters)
    {
        $artifact        = $presenter->getArtifact();
        if ($this->config->isInColumn($artifact, $this->field_provider, $column)) {
            $presenters[] = $presenter;
        }
    }
}
