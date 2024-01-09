<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Tracker\Workflow\Transition\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

class Workflow // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const FUNC_ADMIN_RULES                           = 'admin-workflow';
    public const FUNC_ADMIN_DEPENDENCIES                    = 'admin-dependencies';
    public const FUNC_ADMIN_TRANSITIONS                     = 'admin-workflow-transitions';
    public const FUNC_ADMIN_CROSS_TRACKER_TRIGGERS          = 'admin-workflow-triggers';
    public const FUNC_ADMIN_GET_TRIGGERS_RULES_BUILDER_DATA = 'admin-get-triggers-rules-builder-data';
    public const FUNC_ADMIN_ADD_TRIGGER                     = 'admin-workflow-add-trigger';
    public const FUNC_ADMIN_DELETE_TRIGGER                  = 'admin-workflow-delete-trigger';

    public const BASE_PATH       = '/workflow';
    public const TRANSITION_PATH = '/transitions';

    public $workflow_id;
    public $field_id;
    public $transitions;
    public $is_used;

    /**
     * @var Tracker_FormElement_Field
     */
    protected $field = null;

    /**
     * @var Tracker_FormElement_Field_List_Value[]
     */
    protected $field_values = null;
    private $global_rules_manager;
    private Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager;
    private WorkflowBackendLogger $logger;
    private bool $disabled             = false;
    private ?BeforeEvent $before_event = null;
    /**
     * @var bool
     */
    private $is_legacy;

    /**
     * @var bool
     */
    private $is_advanced;

    public function __construct(
        Tracker_RulesManager $global_rules_manager,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        WorkflowBackendLogger $logger,
        $workflow_id,
        public int $tracker_id,
        $field_id,
        $is_used,
        $is_advanced,
        $is_legacy = false,
        $transitions = null,
    ) {
        $this->workflow_id           = $workflow_id;
        $this->field_id              = $field_id;
        $this->is_used               = $is_used;
        $this->transitions           = $transitions;
        $this->global_rules_manager  = $global_rules_manager;
        $this->trigger_rules_manager = $trigger_rules_manager;
        $this->logger                = $logger;
        $this->is_advanced           = $is_advanced;
        $this->is_legacy             = $is_legacy;
    }

    /**
     * Set field
     *
     * @param Tracker_FormElement_Field $field Field
     */
    public function setField(Tracker_FormElement_Field $field)
    {
        $this->field    = $field;
        $this->field_id = $field->getId();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->workflow_id;
    }

    public function getTrackerId(): int
    {
        return $this->tracker_id;
    }

    /**
     * @return string
     */
    public function getFieldId()
    {
        return $this->field_id;
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getField()
    {
        if (! $this->field) {
            $this->field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());
        }
        return $this->field;
    }

    /**
     * Return all values of the field associated to workflow
     *
     * @return Array of Tracker_FormElement_Field_List_Value
     */
    public function getAllFieldValues()
    {
        if (! $this->field_values) {
            $this->field_values = $this->getField()->getBind()->getAllValues();
        }
        return $this->field_values;
    }

    /**
     * Return the tracker of this workflow
     *
     * @return Tracker
     */
    public function getTracker()
    {
        $tracker = TrackerFactory::instance()->getTrackerById($this->tracker_id);
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        return $tracker;
    }

    /**
     * @return Transition[]
     */
    public function getTransitions()
    {
        if ($this->transitions === null) {
            $this->transitions = TransitionFactory::instance()->getTransitions($this);
        }
        return $this->transitions;
    }

    /**
     * Return transition corresponding to parameters
     *
     * @param int|null $field_value_id_from
     * @param int|null $field_value_id_to
     *
     */
    public function getTransition($field_value_id_from, $field_value_id_to): ?Transition
    {
        foreach ($this->getTransitions() as $transition) {
            $from = $transition->getFieldValueFrom();
            if ($from === null && $field_value_id_from === null || $from !== null && $from->getId() == $field_value_id_from) {
                if ($transition->getFieldValueTo()->getId() == $field_value_id_to) {
                    return $transition;
                }
            }
        }

        return null;
    }

    /**
     * @deprecated since Tuleap 5.8.
     * @see isUsed()
     *
     * @return bool
     */
    public function getIsUsed()
    {
        return $this->isUsed();
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        return $this->is_used;
    }

    /**
     * Test if there is a transition defined between the two list values
     *
     * @param Tracker_FormElement_Field_List_Value $field_value_from
     * @param Tracker_FormElement_Field_List_Value $field_value_to
     *
     * @return bool
     */
    public function isTransitionExist($field_value_from, $field_value_to)
    {
        if ($field_value_from != $field_value_to) {
            $transitions = $this->getTransitions();

            if ($transitions != null) {
                foreach ($transitions as $transition) {
                    if ($transition->equals(new Transition(0, $this->workflow_id, $field_value_from, $field_value_to))) {
                         return true;
                    }
                }

                return false;
            } else {
                return false;
            }
        } else {
            // a non transition (from a value A to the same value A) is always valid
            return true;
        }
    }

    public function hasTransitions()
    {
        if ($this->getTransitions() === []) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Set the tracker of this workflow
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function setTracker(Tracker $tracker)
    {
        $this->tracker_id = $tracker->getId();
    }

    /**
     * Export workflow to XML
     *
     * @param SimpleXMLElement &$root     the node to which the workflow is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $root->addChild('field_id')->addAttribute('REF', array_search($this->field_id, $xmlMapping));
        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'is_used', $this->is_used);
        $child       = $root->addChild('transitions');
        $transitions = $this->getTransitions();
        foreach ($transitions as $transition) {
            $transition->exportToXml($child, $xmlMapping);
        }
    }

    /**
     * Execute actions before transition happens (if there is one)
     *
     * @param array    $fields_data  Request field data (array[field_id] => data)
     * @param PFUser   $current_user The user who are performing the update
     * @param Artifact $artifact     The artifact
     *
     * @return void
     */
    public function before(array &$fields_data, PFUser $current_user, Artifact $artifact)
    {
        $artifact_id = $artifact->getId();
        if (! $this->is_used && ! $this->is_legacy) {
            $this->logger->debug("Workflow for artifact #$artifact_id is disabled, skipping transitions.");
        } else {
            $transition = $this->getCurrentTransition($fields_data, $artifact->getLastChangeset());
            if ($transition) {
                $transition->before($fields_data, $current_user);
            }
        }

        $this->before_event = new BeforeEvent($artifact, $fields_data, $current_user);
        EventManager::instance()->processEvent($this->before_event);

        $fields_data = $this->before_event->getFieldsData();
    }

    /**
     * Execute actions after transition happens (if there is one)
     *
     * @param array $fields_data Request field data (array[field_id] => data)
     * @param Tracker_Artifact_Changeset $new_changeset The changeset that has just been created
     * @param ?Tracker_Artifact_Changeset $previous_changeset The changeset just before (null for a new artifact)
     *
     * @return void
     */
    public function after(
        array $fields_data,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
        $artifact_id = $new_changeset->getArtifact()->getId();

        $this->logger->defineFingerprint($artifact_id);
        $this->logger->start(__METHOD__, $new_changeset->getId(), ($previous_changeset ? $previous_changeset->getId() : 'null'));

        if (! $this->is_used && ! $this->is_legacy) {
            $this->logger->debug("Workflow for artifact #$artifact_id is disabled, skipping transitions.");
        } else {
            $transition = $this->getCurrentTransition($fields_data, $previous_changeset);
            if ($transition) {
                $transition->after($new_changeset);
            }
        }

        $this->trigger_rules_manager->processTriggers($new_changeset);

        $this->logger->end(__METHOD__, $new_changeset->getId(), ($previous_changeset ? $previous_changeset->getId() : 'null'));
    }

    /**
     * @throws Tracker_Workflow_Transition_InvalidConditionForTransitionException
     */
    public function validate($fields_data, Artifact $artifact, string $comment_body, PFUser $current_user): void
    {
        if (! $this->is_used) {
            return;
        }

        $transition = $this->getCurrentTransition($fields_data, $artifact->getLastChangeset());
        if (isset($transition)) {
            if (! $transition->validate($fields_data, $artifact, $comment_body, $current_user)) {
                throw new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition);
            }
        }
    }

    private function getCurrentTransition(
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ): ?Transition {
        return $this->getTransitionRetriever()->retrieveTransition(
            $this,
            $fields_data,
            $previous_changeset
        );
    }

    /**
     * For testing purpose
     */
    protected function getTransitionRetriever(): TransitionRetriever
    {
        return new TransitionRetriever();
    }

    /**
     * @throws Tracker_Workflow_GlobalRulesViolationException
     */
    public function checkGlobalRules(array $fields_data)
    {
        if ($this->disabled) {
            return true;
        }
        if (! $this->global_rules_manager->validate($this->tracker_id, $fields_data)) {
            throw new Tracker_Workflow_GlobalRulesViolationException();
        }
    }

    /**
     *
     * @return Tracker_RulesManager
     */
    public function getGlobalRulesManager()
    {
        return $this->global_rules_manager;
    }

    public function disable()
    {
        $this->is_used  = false;
        $this->disabled = true;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function bypassPermissions(Tracker_FormElement_Field $field): bool
    {
        if ($this->disabled === true) {
            return true;
        }

        $transitions = $this->getTransitions();
        foreach ($transitions as $transition) {
            if ($transition->bypassPermissions($field)) {
                return true;
            }
        }

        if ($this->before_event) {
            return $this->before_event->shouldBypassPermissions($field);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isLegacy()
    {
        return $this->is_legacy;
    }

    /**
     * @return bool
     */
    public function isAdvanced()
    {
        return $this->is_advanced;
    }
}
