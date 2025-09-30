<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\Duplication\DuplicationUserGroupMapping;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\Event\TransitionDeletionEvent;
use Tuleap\Tracker\Workflow\Event\WorkflowDeletionEvent;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use Tuleap\Tracker\Workflow\TransitionDeletionException;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class TransitionFactory
{
    /** @var Workflow_Transition_ConditionFactory */
    private $condition_factory;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var array<int, array<int, Transition>>
     */
    private $workflow_cache;
    /**
     * @var Transition_PostActionFactory
     */
    private $transition_post_action_factory;
    /**
     * @var Workflow_TransitionDao
     */
    private $dao;

    public function __construct(
        Workflow_Transition_ConditionFactory $condition_factory,
        EventManager $event_manager,
        DBTransactionExecutor $db_transaction_executor,
        Transition_PostActionFactory $transition_post_action_factory,
        Workflow_TransitionDao $dao,
    ) {
        $this->condition_factory              = $condition_factory;
        $this->event_manager                  = $event_manager;
        $this->db_transaction_executor        = $db_transaction_executor;
        $this->transition_post_action_factory = $transition_post_action_factory;
        $this->dao                            = $dao;
    }

    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * The singleton method
     *
     * @return TransitionFactory
     */
    public static function instance(): self
    {
        if (! isset(self::$_instance)) {
            self::$_instance = new self(
                Workflow_Transition_ConditionFactory::build(),
                EventManager::instance(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                ),
                new Transition_PostActionFactory(
                    EventManager::instance()
                ),
                new Workflow_TransitionDao()
            );
        }
        return self::$_instance;
    }

    /**
     * Build a Transition instance
     *
     * @param array    $row      The data describing the transition
     * @param Workflow $workflow Workflow the transition belongs to
     */
    public function getInstanceFromRow(array $row, Workflow $workflow): ?Transition
    {
        $transition = $this->buildTransition($row['from_id'], $row['to_id'], $workflow, $row['transition_id']);
        if (! $transition) {
            return null;
        }
        $this->transition_post_action_factory->loadPostActions($transition);
        return $transition;
    }

    /**
     * @param int       $transition_id
     * @param int       $from_id
     * @param int       $to_id
     * @param Workflow  $workflow
     *
     */
    private function buildTransition($from_id, $to_id, $workflow, $transition_id = null): ?Transition
    {
        $field_values = $workflow->getAllFieldValues();
        $from         = null;
        $to           = null;
        if (isset($field_values[$from_id])) {
            $from = $field_values[$from_id];
        }
        if (isset($field_values[$to_id])) {
            $to = $field_values[$to_id];
        }

        if (! $to) {
            return null;
        }

        return new Transition(
            $transition_id,
            $workflow->getId(),
            $from,
            $to
        );
    }

    /**
     * @param int transition_id The transition_id
     */
    public function getTransition($transition_id): ?Transition
    {
        if ($row = $this->dao->searchById($transition_id)->getRow()) {
            $workflow = WorkflowFactory::instance()->getWorkflow($row['workflow_id']);
            if ($workflow) {
                return $this->getInstanceFromRow($row, $workflow);
            }
        }
        return null;
    }

    protected $cache_transition_id = [];

    public function getTransitionId(Tracker $tracker, $from, $to)
    {
        if ($tracker->getWorkflow() instanceof WorkflowWithoutTransition) {
            return null;
        }

        $tracker_id = $tracker->getId();

        if ($from != null) {
            $from = $from->getId();
        } elseif ($from === null) {
            $from = 0;
        }

        if (! isset($this->cache_transition_id[$tracker_id])) {
            foreach ($this->dao->searchByTrackerId($tracker_id) as $row) {
                $row_from = (int) $row['from_id'];
                $row_to   = (int) $row['to_id'];

                $this->cache_transition_id[$tracker_id][$row_from][$row_to] = $row['transition_id'];
            }
        }

        if (! isset($this->cache_transition_id[$tracker_id][$from][$to])) {
            return null;
        }

        return $this->cache_transition_id[$tracker_id][$from][$to];
    }

    /**
     * Say if a field is used in its tracker workflow transitions
     *
     * @param TrackerField $field The field
     *
     * @return bool
     */
    public function isFieldUsedInTransitions(TrackerField $field)
    {
        return $this->transition_post_action_factory->isFieldUsedInPostActions($field)
            || $this->condition_factory->isFieldUsedInConditions($field);
    }

    /**
     * Creates a transition Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported workflow
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     *
     * @return Transition | null The transition object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Project $project)
    {
        $from = null;
        if (isset($xmlMapping[(string) $xml->from_id['REF']]) && (string) $xml->from_id['REF'] != 'null') {
            $from = $xmlMapping[(string) $xml->from_id['REF']];
        }

        if (! isset($xmlMapping[(string) $xml->to_id['REF']])) {
            return null;
        }
        $to = $xmlMapping[(string) $xml->to_id['REF']];

        return $this->buildTransitionFromXML($xml, $project, $xmlMapping, $from, $to);
    }

    /**
     * @return Transition[]
     */
    public function getInstancesFromStateXML(
        SimpleXMLElement $state_xml,
        array &$xml_mapping,
        Project $project,
        Tracker_FormElement_Field_List_Value $to_value,
    ) {
        $transitions = [];
        foreach ($state_xml->transitions->transition as $transition_xml) {
            $from_value = null;
            if ((string) $transition_xml->from_id['REF'] !== 'null') {
                $from_value = $xml_mapping[(string) $transition_xml->from_id['REF']];
            }

            $transitions[] = $this->buildTransitionFromXML($state_xml, $project, $xml_mapping, $from_value, $to_value);
        }

        return $transitions;
    }

    /**
     * @return Transition
     */
    private function buildTransitionFromXML(
        SimpleXMLElement $xml,
        Project $project,
        array $xml_mapping,
        ?Tracker_FormElement_Field_List_Value $from_value,
        Tracker_FormElement_Field_List_Value $to_value,
    ) {
        $transition  = new Transition(0, 0, $from_value, $to_value);
        $postactions = [];
        if ($xml->postactions) {
            $postactions = $this->transition_post_action_factory->getInstanceFromXML(
                $xml->postactions,
                $xml_mapping,
                $transition
            );
        }
        $transition->setPostActions($postactions);

        // Conditions on transition
        $transition->setConditions(
            $this->condition_factory->getAllInstancesFromXML($xml, $xml_mapping, $transition, $project)
        );

        return $transition;
    }

    /**
     * Delete a workflow
     *
     *
     * @return bool
     */
    public function deleteWorkflow(Workflow $workflow)
    {
        $transitions = $this->getTransitions($workflow);
        $workflow_id = $workflow->getId();

        $this->dao->startTransaction();

        //Delete permissions
        foreach ($transitions as $transition) {
            $transition_id = $transition->getTransitionId();

            permission_clear_all(
                $workflow->getTracker()->getGroupId(),
                Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION,
                $transition_id,
                false
            );
        }

        $event = new WorkflowDeletionEvent($workflow);
        $this->event_manager->processEvent($event);

        $result = $this->dao->deleteWorkflowTransitions($workflow_id);
        if ($result === false) {
            $this->dao->rollBack();
        }
        $this->dao->commit();

        return true;
    }

    /**
     * Get the transitions of the workflow
     *
     * @param Workflow $workflow The workflow
     *
     * @return Transition[]
     */
    public function getTransitions(Workflow $workflow): array
    {
        $workflow_id = (int) $workflow->getId();
        if (! isset($this->workflow_cache[$workflow_id])) {
            $this->workflow_cache[$workflow_id] = [];
            $this->transition_post_action_factory->warmUpCacheForWorkflow($workflow);
            foreach ($this->dao->searchByWorkflow($workflow->getId()) as $row) {
                $transition = $this->getInstanceFromRow($row, $workflow);
                if ($transition) {
                    $this->workflow_cache[$workflow_id][(int) $transition->getId()] = $transition;
                }
            }
        }
        return $this->workflow_cache[$workflow_id];
    }

    /**
     * Get the transitions of the workflow for a given destination value
     *
     * @return Transition[]
     */
    public function getTransitionsForAGivenDestination(Workflow $workflow, int $to_id): array
    {
        return array_filter(
            $this->getTransitions($workflow),
            static function (Transition $transition) use ($to_id) {
                return (int) $transition->getIdTo() === $to_id;
            }
        );
    }

    /**
     * Creates transition in the database
     *
     * @param int $workflow_id The workflow_id of the transitions to save
     * @param Transition          $transition The transition
     *
     * @return void
     */
    public function saveObject($workflow_id, $transition)
    {
        $transition_from = $transition->getFieldValueFrom();
        if ($transition_from == null) {
            $from_id = null;
        } else {
            $from_id = $transition_from->getId();
        }
        $to_id         = $transition->getFieldValueTo()->getId();
        $transition_id = $this->dao->addTransition($workflow_id, $from_id, $to_id);
        $transition->setTransitionId($transition_id);

        //Save postactions
        $postactions = $transition->getAllPostActions();
        foreach ($postactions as $postaction) {
            $this->transition_post_action_factory->saveObject($postaction);
        }

        //Save conditions
        $transition->getConditions()->saveObject();
    }

    /**
     * Generate transition object and save it in database.
     */
    public function createAndSaveTransition(Workflow $workflow, TransitionCreationParameters $parameters): ?Transition
    {
        $new_transition = $this->buildTransition($parameters->getFromId(), $parameters->getToId(), $workflow, null);
        if (! $new_transition) {
            return null;
        }
        $this->saveObject((int) $workflow->getId(), $new_transition);

        return $new_transition;
    }

    /**
     * Adds permissions in the database
     *
     * @param array $ugroups_ids the list of ugroups ids
     * @param int $transition_id  The transition id
     *
     * @return bool
     */
    public function addPermissions(array $ugroups_ids, $transition_id)
    {
        $pm              = PermissionsManager::instance();
        $permission_type = 'PLUGIN_TRACKER_WORKFLOW_TRANSITION';
        foreach ($ugroups_ids as $ugroup_id) {
            if (! $pm->addPermission($permission_type, (int) $transition_id, $ugroup_id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $values array of old and new values of the field
     * @param int $workflow_id the workflow id
     * @param Transition[] $transitions the transitions to duplicate
     */
    public function duplicate($values, $workflow_id, array $transitions, array $field_mapping, DuplicationUserGroupMapping $duplication_user_group_mapping): void
    {
        if ($transitions != null) {
            foreach ($transitions as $transition) {
                if ($transition->getFieldValueFrom() == null) {
                    $from_id = 'null';
                    $to      = $transition->getFieldValueTo()->getId();
                    foreach ($values as $value => $id_value) {
                        if ($value == $to) {
                            $to_id = $id_value;
                        }
                    }
                } else {
                    $from = $transition->getFieldValueFrom()?->getId();
                    $to   = $transition->getFieldValueTo()->getId();
                    foreach ($values as $value => $id_value) {
                        if ($value == $from) {
                            $from_id = $id_value;
                        }
                        if ($value == $to) {
                            $to_id = $id_value;
                        }
                    }
                }

                $new_transition_id = $this->addTransition($workflow_id, $from_id, $to_id);

                // Duplicate permissions
                $this->condition_factory->duplicate($transition, $new_transition_id, $field_mapping, $duplication_user_group_mapping);

                // Duplicate postactions
                $this->transition_post_action_factory->duplicate($transition, $new_transition_id, $field_mapping);
            }
        }
    }

    /**
     * Add a transition in db
     *
     * @param int $workflow_id the old transition id
     * @param int $from_id the new transition id
     * @param int $to_id the ugroup mapping
     */
    public function addTransition($workflow_id, $from_id, $to_id): int
    {
        return $this->dao->addTransition($workflow_id, $from_id, $to_id);
    }

    /**
     *
     * @throws TransitionDeletionException
     */
    public function delete(Transition $transition)
    {
        $this->db_transaction_executor->execute(function () use ($transition) {
            try {
                $event = new TransitionDeletionEvent($transition);
                $this->event_manager->processEvent($event);

                if (
                    ! $this->dao->deleteTransition(
                        $transition->getWorkflow()->getId(),
                        $transition->getIdFrom(),
                        $transition->getIdTo()
                    )
                ) {
                    throw new TransitionDeletionException();
                }
            } catch (DataAccessQueryException $exception) {
                throw new TransitionDeletionException($exception);
            }
        });
    }
}
