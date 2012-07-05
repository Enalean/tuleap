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

require_once CARDWALL_BASE_DIR .'/OnTop/Config/Command.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnMappingFieldDao.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/TrackerFactory.class.php';

/**
 * Create a MappingField for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_DeleteMappingFields extends Cardwall_OnTop_Config_Command {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(Tracker $tracker, Cardwall_OnTop_ColumnMappingFieldDao $dao, TrackerFactory $tracker_factory) {
        parent::__construct($tracker);
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request) {
        if (is_array($request->get('delete_mapping'))) {
            foreach ($request->get('delete_mapping') as $mapping_tracker_id) {
                $mapping_tracker = $this->tracker_factory->getTrackerById($mapping_tracker_id);
                if ($mapping_tracker && $this->dao->delete($this->tracker->getId(), $mapping_tracker_id)) {
                    $GLOBALS['Response']->addFeedback('info', 'Mapping on '. $mapping_tracker->getName() .' removed');
                }
            }
        }
    }
}
?>
