<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
 * Update a column for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_UpdateColumns extends Cardwall_OnTop_Config_Command
{

    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    public function __construct(Tracker $tracker, Cardwall_OnTop_ColumnDao $dao)
    {
        parent::__construct($tracker);
        $this->dao = $dao;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request)
    {
        if ($request->get('column')) {
            foreach ($request->get('column') as $id => $column_definition) {
                $column_label    = $column_definition['label'];

                $success = $this->saveColors($column_definition, $column_label, $id);

                if ($success) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_column_changed', array($column_label)));
                }
            }
        }
    }

    private function saveColors($column_definition, $column_label, $id)
    {
        if (! isset($column_definition['bgcolor']) || ! isset($column_label)) {
            return;
        }

        $color = $column_definition['bgcolor'];

        if (Tracker_FormElement_Field_List_BindDecorator::isHexaColor($color)) {
            list($column_bg_red, $column_bg_green, $column_bg_blue) = ColorHelper::HexatoRGB($color);

            return $this->dao->save($this->tracker->getId(), $id, $column_label, $column_bg_red, $column_bg_green, $column_bg_blue);
        }

        return $this->dao->saveTlpColor($this->tracker->getId(), $id, $column_label, $color);
    }
}
