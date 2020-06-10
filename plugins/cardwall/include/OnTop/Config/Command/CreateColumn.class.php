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
 * Create a column for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_CreateColumn extends Cardwall_OnTop_Config_Command
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
        if ($request->get('new_column')) {
            $this->dao->create($this->tracker->getId(), $request->get('new_column'));
            $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-cardwall', 'Column added'));
        }
    }
}
