<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use SimpleXMLElement;
use Transition;
use Transition_PostAction;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Workflow;

class HiddenFieldsetsFactory implements \Transition_PostActionSubFactory
{
    /**
     * @var HiddenFieldsetsDao
     */
    private $hidden_fieldsets_dao;
    /**
     * @var HiddenFieldsetsRetriever
     */
    private $hidden_fieldsets_retriever;

    public function __construct(
        HiddenFieldsetsDao $hidden_fieldsets_dao,
        HiddenFieldsetsRetriever $hidden_fieldsets_retriever,
    ) {
        $this->hidden_fieldsets_dao       = $hidden_fieldsets_dao;
        $this->hidden_fieldsets_retriever = $hidden_fieldsets_retriever;
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $this->hidden_fieldsets_retriever->warmUpCacheForWorkflow($workflow);
    }

    /**
     * @return HiddenFieldsets[]
     */
    public function loadPostActions(Transition $transition): array
    {
        try {
            return [$this->hidden_fieldsets_retriever->getHiddenFieldsets($transition)];
        } catch (NoHiddenFieldsetsPostActionException $exception) {
        }
        return [];
    }

    /**
     * Save a postaction object
     *
     * @param Transition_PostAction $post_action the object to save
     *
     * @return void
     */
    public function saveObject(Transition_PostAction $post_action)
    {
        $to_transition_id = (int) $post_action->getTransition()->getId();

        $fieldset_ids = [];
        assert($post_action instanceof HiddenFieldsets);
        foreach ($post_action->getFieldsets() as $fieldset) {
            $fieldset_ids[] = (int) $fieldset->getID();
        }

        $this->hidden_fieldsets_dao->createPostActionForTransitionId(
            $to_transition_id,
            $fieldset_ids
        );
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param TrackerField $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(TrackerField $field)
    {
        // No field used in this post action, only fieldsets.
        return false;
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition the template transition
     * @param int $to_transition_id the id of the transition
     * @param array $field_mapping the field mapping
     *
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $postactions = $this->loadPostActions($from_transition);
        foreach ($postactions as $postaction) {
            $from_fieldsets  = $postaction->getFieldsets();
            $to_fieldset_ids = [];

            $from_fieldset_ids = [];
            foreach ($from_fieldsets as $fieldset) {
                $from_fieldset_ids[] = (int) $fieldset->getID();
            }

            foreach ($field_mapping as $mapping) {
                foreach ($from_fieldset_ids as $from_fieldset_id) {
                    if ($mapping['from'] == $from_fieldset_id) {
                        $to_fieldset_ids[] = $mapping['to'];
                    }
                }
            }

            $this->hidden_fieldsets_dao->createPostActionForTransitionId($to_transition_id, $to_fieldset_ids);
        }
    }

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement $xml containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition $transition to which the postaction is attached
     *
     * @return Transition_PostAction|null The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        $fieldsets = [];
        foreach ($xml->fieldset_id as $xml_fieldset_id) {
            if (isset($xmlMapping[(string) $xml_fieldset_id['REF']])) {
                $fieldsets[] = $xmlMapping[(string) $xml_fieldset_id['REF']];
            }
        }

        if (count($fieldsets) > 0) {
            return new HiddenFieldsets($transition, 0, $fieldsets);
        }

        return null;
    }
}
