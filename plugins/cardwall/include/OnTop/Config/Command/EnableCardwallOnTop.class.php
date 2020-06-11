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
 * Activate or deactivate the cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_EnableCardwallOnTop extends Cardwall_OnTop_Config_Command
{

    /**
     * @var Cardwall_OnTop_Dao
     */
    private $dao;

    public function __construct(Tracker $tracker, Cardwall_OnTop_Dao $dao)
    {
        parent::__construct($tracker);
        $this->dao = $dao;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request)
    {
        $please_enable = $request->get('cardwall_on_top');
        $tracker_id    = $this->tracker->getId();
        $is_enabled    = $this->dao->isEnabled($tracker_id);
        if ($please_enable) {
            if (! $is_enabled) {
                $this->dao->enable($tracker_id);
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-cardwall', 'Now a cardwall exists on top of the tracker'));
            }
        } else {
            if ($is_enabled) {
                $this->dao->disable($tracker_id);
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-cardwall', 'Cardwall disabled'));
            }
        }
    }
}
