<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Tracker\Rule\TrackerRulesDateValidator;
use Tuleap\Tracker\Rule\TrackerRulesListValidator;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;

class WorkflowFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    /** @var TransitionFactory */
    private $transition_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_Workflow_Trigger_RulesManager */
    private $trigger_rules_manager;

    /** @var WorkflowBackendLogger */
    private $logger;

    /** @var FrozenFieldsDao */
    private $read_only_dao;
    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * Should use the singleton instance()
     *
     */
    public function __construct(
        TransitionFactory $transition_factory,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        WorkflowBackendLogger $logger,
        FrozenFieldsDao $read_only_dao,
        StateFactory $state_factory
    ) {
        $this->transition_factory    = $transition_factory;
        $this->tracker_factory       = $tracker_factory;
        $this->formelement_factory   = $formelement_factory;
        $this->trigger_rules_manager = $trigger_rules_manager;
        $this->logger                = $logger;
        $this->read_only_dao         = $read_only_dao;
        $this->state_factory         = $state_factory;
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    /**
     * The singleton method
     *
     * @return WorkflowFactory
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $formelement_factory = Tracker_FormElementFactory::instance();
            $logger = new WorkflowBackendLogger(BackendLogger::getDefaultLogger(), ForgeConfig::get('sys_logger_level'));

            $trigger_rules_manager = new Tracker_Workflow_Trigger_RulesManager(
                new Tracker_Workflow_Trigger_RulesDao(),
                $formelement_factory,
                new Tracker_Workflow_Trigger_RulesProcessor(
                    UserManager::instance()->getUserById(Tracker_Workflow_WorkflowUser::ID),
                    $logger
                ),
                $logger,
                new Tracker_Workflow_Trigger_RulesBuilderFactory($formelement_factory),
                new WorkflowRulesManagerLoopSafeGuard($logger)
            );

            $c = self::class;
            self::$_instance = new $c(
                TransitionFactory::instance(),
                TrackerFactory::instance(),
                $formelement_factory,
                $trigger_rules_manager,
                $logger,
                new FrozenFieldsDao(),
                new StateFactory(
                    TransitionFactory::instance(),
                    new SimpleWorkflowDao()
                )
            );
        }
        return self::$_instance;
    }

    /**
     * @return Tracker_Workflow_Trigger_RulesManager
     */
    public function getTriggerRulesManager()
    {
        return $this->trigger_rules_manager;
    }

    /**
     * Build a Workflow instance
     *
     * @param array $row The data describing the workflow
     *
     * @return Workflow
     */
    public function getInstanceFromRow($row)
    {
        $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }

        return new Workflow(
            $this->getGlobalRulesManager($tracker),
            $this->trigger_rules_manager,
            $this->logger,
            $row['workflow_id'],
            $row['tracker_id'],
            $row['field_id'],
            $row['is_used'],
            $row['is_advanced'],
            $row['is_legacy']
        );
    }

    /**
     * @param int $workflow_id
     * @return null|Workflow
     */
    public function getWorkflow($workflow_id)
    {
        if ($row = $this->getDao()->searchById($workflow_id)->getRow()) {
            return $this->getInstanceFromRow($row);
        }
        return null;
    }


    /**
     * @return WorkflowWithoutTransition
     */
    public function getWorkflowWithoutTransition(Tracker $tracker)
    {
        return new WorkflowWithoutTransition(
            $this->getGlobalRulesManager($tracker),
            $this->trigger_rules_manager,
            $this->logger,
            $tracker->getId()
        );
    }

    /**
     * Create a workflow
     *
     * @param int $tracker_id The tracker the workflow refers to
     * @param string $field_id the field_id on which the workflow applies
     *
     * @return int the id of the workflow. False if error
     */
    public function create($tracker_id, $field_id)
    {
        return $this->getDao()->create($tracker_id, $field_id);
    }

    /**
     * Update workflow activation
     *
     * @param int $workflow_id the workflow id
     * @param int $is_used 1 if enable, 0 otherwise.
     *
     * @return int the id of the workflow. False if error
     */
    public function updateActivation($workflow_id, $is_used)
    {
        return $this->getDao()->updateActivation($workflow_id, $is_used);
    }

    /**
     * Delete a workflow
     *
     * @param int $workflow_id the workflow id
     */
    public function delete($workflow_id)
    {
        return $this->getDao()->delete($workflow_id);
    }

    /**
     * Delete a workflow
     *
     * @param int $workflow_id the workflow id
     */
    public function deleteWorkflow($workflow_id)
    {
        $workflow = $this->getWorkflow($workflow_id);
        if ($this->transition_factory->deleteWorkflow($workflow)) {
            return $this->delete($workflow_id);
        }
    }

    /**
     * Get a transition id
     *
     * @param int $workflow_id The workflow id
     * @param string $transition the transition to insert
     *
     * @return int the id of the transition. False if error
     */
    public function getTransitionId($workflow_id, $transition)
    {
        $values = explode("_", $transition);
        $from = $values[0];
        $to = $values[1];
        return $this->getTransitionDao()->searchTransitionId($workflow_id, $from, $to);
    }

    protected $cache_workflowfield;

    /**
     * Get the Workflow object for the tracker $tracker_id
     *
     * @param int $tracker_id the Id of the tracker
     *
     * @return Workflow|null
     */
    public function getWorkflowByTrackerId(?int $tracker_id)
    {
        if (!isset($this->cache_workflowfield[$tracker_id])) {
            $this->cache_workflowfield[$tracker_id] = array(null);
            // only one field per workflow
            if ($row = $this->getDao()->searchByTrackerId($tracker_id)->getRow()) {
                $this->cache_workflowfield[$tracker_id] = array($this->getInstanceFromRow($row));
            }
        }
        return $this->cache_workflowfield[$tracker_id][0];
    }

    /**
     * Clear Tracker workflow from $cache_workflowfield
     */
    public function clearTrackerWorkflowFromCache(Tracker $tracker)
    {
        unset($this->cache_workflowfield[(int) $tracker->getId()]);
    }

    /**
     * Say if a field is used in its tracker workflow or post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInWorkflow(Tracker_FormElement_Field $field)
    {
        return $this->isWorkflowField($field) || $this->transition_factory->isFieldUsedInTransitions($field);
    }

    /**
     * Say if a field is used to define a workflow
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isWorkflowField(Tracker_FormElement_Field $field)
    {
        $workflow = $this->getWorkflowByTrackerId($field->getTracker()->getId());
        if ($workflow) {
            return $field->getId() == $workflow->getFieldId();
        }
        return false;
    }

    /**
     * Duplicate the workflow
     *
     * @param $from_tracker_id the template tracker id
     * @param $to_tracker_id the tracker id
     * @param $from_id the id of the field
     * @param $to_id the id of the duplicated field
     * @param Array $values array of old and new values of the field
     * @param Array $field_mapping the field mapping
     * @param Array $ugroup_mapping the ugroup mapping
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id, $from_id, $to_id, $values, $field_mapping, $ugroup_mapping, $duplicate_type)
    {
        if ($workflow = $this->getWorkflowByTrackerId($from_tracker_id)) {
            $is_used = $workflow->getIsUsed();
            $is_advanced = $workflow->isAdvanced();

            //Duplicate workflow
            if ($id = $this->getDao()->duplicate($to_tracker_id, $to_id, $is_used, $is_advanced)) {
                $transitions = $workflow->getTransitions();
                //Duplicate transitions
                $this->transition_factory->duplicate($values, $id, $transitions, $field_mapping, $ugroup_mapping, $duplicate_type);
            }
        }
    }

    /**
     * Creates a workflow Object
     *
     * @param SimpleXMLElement $xml containing the structure of the imported workflow
     * @param array            &$xml_mapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker $tracker to which the workflow is attached
     *
     * @return Workflow The workflow object, or null if error
     */
    public function getInstanceFromXML(SimpleXMLElement $xml, array &$xml_mapping, Tracker $tracker, Project $project)
    {
        $workflow_field = $this->getWorkflowField($xml, $xml_mapping);

        $transitions = array();
        foreach ($xml->transitions->transition as $t) {
            $tf = $this->transition_factory;
            $transitions[] = $tf->getInstanceFromXML($t, $xml_mapping, $project);
        }

        $workflow = new Workflow(
            $this->getGlobalRulesManager($tracker),
            $this->trigger_rules_manager,
            $this->logger,
            0, // not available yet
            $tracker->getId(),
            0, // not available yet
            (string) $xml->is_used,
            true, // For legacy compatibility
            false,
            $transitions
        );
        if ($workflow_field) {
            $workflow->setField($workflow_field);
        }

        return $workflow;
    }

    public function getSimpleInstanceFromXML(
        SimpleXMLElement $xml,
        array &$xml_mapping,
        Tracker $tracker,
        Project $project
    ) {
        $workflow_field = $this->getWorkflowField($xml, $xml_mapping);
        $transitions    = [];

        foreach ($xml->states->state as $state_xml) {
            $state = $this->state_factory->getInstanceFromXML($state_xml, $xml_mapping, $project);
            $transitions = array_merge(
                $transitions,
                $state->getTransitions()
            );
        }

        $workflow = new Workflow(
            $this->getGlobalRulesManager($tracker),
            $this->trigger_rules_manager,
            $this->logger,
            0, // not available yet
            $tracker->getId(),
            0, // not available yet
            (string) $xml->is_used,
            false,
            false,
            $transitions
        );

        $workflow->setField($workflow_field);
        return $workflow;
    }

    private function getWorkflowField(SimpleXMLElement $xml, array $xml_mapping)
    {
        $xml_field_id = $xml->field_id;
        $xml_field_attributes = $xml_field_id->attributes();
        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }
        $field = $xml_mapping[(string) $xml_field_attributes['REF']];

        return $field;
    }

    /**
     * Creates new workflow in the database
     *
     * @param Workflow $workflow The workflow to save
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function saveObject($workflow, $tracker)
    {
        $workflow->setTracker($tracker);
        $dao = $this->getDao();

        $workflow_id = $dao->save(
            $workflow->tracker_id,
            $workflow->getField()->getId(),
            $workflow->is_used,
            $workflow->isAdvanced()
        );

        //Save transitions
        foreach ($workflow->getTransitions() as $transition) {
            $tf = $this->transition_factory;
            $tf->saveObject($workflow_id, $transition);
        }
    }

    public function getGlobalRulesManager(Tracker $tracker)
    {
        return new Tracker_RulesManager(
            $tracker,
            $this->formelement_factory,
            $this->read_only_dao,
            new TrackerRulesListValidator($this->formelement_factory),
            new TrackerRulesDateValidator($this->formelement_factory),
            $this->tracker_factory
        );
    }

    /**
     * Get the Workflow dao
     *
     * @return Workflow_Dao
     */
    protected function getDao()
    {
        return new Workflow_Dao();
    }

    /**
     * Get the Workflow Transition dao
     *
     * @return Workflow_TransitionDao
     */
    protected function getTransitionDao()
    {
        return new Workflow_TransitionDao();
    }
}
