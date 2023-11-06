<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

class Cardwall_OnTop_ConfigFactory
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    public function __construct(TrackerFactory $tracker_factory, Tracker_FormElementFactory $element_factory)
    {
        $this->tracker_factory = $tracker_factory;
        $this->element_factory = $element_factory;
    }

    /**
     * @return \Cardwall_OnTop_Config
     */
    public function getOnTopConfigByTrackerId($tracker_id)
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        return $this->getOnTopConfig($tracker);
    }

    /**
     *
     * @return \Cardwall_OnTop_Config
     */
    public function getOnTopConfig(Tracker $tracker)
    {
        $column_factory = new Cardwall_OnTop_Config_ColumnFactory($this->getOnTopColumnDao());

        $value_mapping_factory = new Cardwall_OnTop_Config_ValueMappingFactory(
            $this->element_factory,
            $this->getOnTopColumnMappingFieldValueDao()
        );

        $tracker_mapping_factory = new Cardwall_OnTop_Config_TrackerMappingFactory(
            $this->tracker_factory,
            $this->element_factory,
            $this->getOnTopColumnMappingFieldDao(),
            $value_mapping_factory
        );

        $config = new Cardwall_OnTop_Config(
            $tracker,
            $this->getOnTopDao(),
            $column_factory,
            $tracker_mapping_factory
        );
        return $config;
    }

    /**
     * Returns the cardwall configuration of the given planning
     *
     *
     * @return Cardwall_OnTop_Config | null
     */
    public function getOnTopConfigByPlanning(Planning $planning)
    {
        $tracker = $planning->getPlanningTracker();
        if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
            return $this->getOnTopConfig($tracker);
        }
        return null;
    }

    /**
     * @return Cardwall_OnTop_Config_Updater
     */
    public function getOnTopConfigUpdater(Tracker $tracker)
    {
        $tracker_factory  = $this->tracker_factory;
        $element_factory  = $this->element_factory;
        $config           = $this->getOnTopConfig($tracker);
        $dao              = $this->getOnTopDao();
        $column_dao       = $this->getOnTopColumnDao();
        $mappingfield_dao = $this->getOnTopColumnMappingFieldDao();
        $mappingvalue_dao = $this->getOnTopColumnMappingFieldValueDao();
        $updater          = new Cardwall_OnTop_Config_Updater();
        $updater->addCommand(new Cardwall_OnTop_Config_Command_EnableCardwallOnTop($tracker, $dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_EnableFreestyleColumns($tracker, $dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_CreateColumn($tracker, $column_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $column_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_DeleteColumns($tracker, $column_dao, $mappingfield_dao, $mappingvalue_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_CreateMappingField($tracker, $mappingfield_dao, $tracker_factory));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_UpdateMappingFields($tracker, $mappingfield_dao, $mappingvalue_dao, $tracker_factory, $element_factory, $config->getMappings()));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_DeleteMappingFields($tracker, $mappingfield_dao, $mappingvalue_dao, $tracker_factory, $config->getMappings()));
        return $updater;
    }

    /**
     * @return Cardwall_OnTop_Dao
     */
    private function getOnTopDao()
    {
        return new Cardwall_OnTop_Dao();
    }

    /**
     * @return Cardwall_OnTop_ColumnDao
     */
    private function getOnTopColumnDao()
    {
        return new Cardwall_OnTop_ColumnDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldDao
     */
    private function getOnTopColumnMappingFieldDao()
    {
        return new Cardwall_OnTop_ColumnMappingFieldDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private function getOnTopColumnMappingFieldValueDao()
    {
        return new Cardwall_OnTop_ColumnMappingFieldValueDao();
    }
}
