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
 * Create a MappingField for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_CreateMappingField extends Cardwall_OnTop_Config_Command
{

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(Tracker $tracker, Cardwall_OnTop_ColumnMappingFieldDao $dao, TrackerFactory $tracker_factory)
    {
        parent::__construct($tracker);
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request)
    {
        if ($request->get('add_mapping_on')) {
            $new_mapping_tracker = $this->tracker_factory->getTrackerById($request->get('add_mapping_on'));
            if ($new_mapping_tracker && $this->dao->create($this->tracker->getId(), $new_mapping_tracker->getId(), null)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_mapping_added', array($new_mapping_tracker->getName())));
            }
        }
    }
}
