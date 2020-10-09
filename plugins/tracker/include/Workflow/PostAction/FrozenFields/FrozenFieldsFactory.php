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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use SimpleXMLElement;
use Tracker_FormElement_Field;
use Transition;
use Transition_PostAction;
use Workflow;

class FrozenFieldsFactory implements \Transition_PostActionSubFactory
{
    /** @var FrozenFieldsDao */
    private $frozen_dao;
    /**
     * @var FrozenFieldsRetriever
     */
    private $frozen_fields_retriever;

    public function __construct(
        FrozenFieldsDao $frozen_dao,
        FrozenFieldsRetriever $frozen_fields_retriever
    ) {
        $this->frozen_dao           = $frozen_dao;
        $this->frozen_fields_retriever = $frozen_fields_retriever;
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $this->frozen_fields_retriever->warmUpCacheForWorkflow($workflow);
    }

    /**
     * @return FrozenFields[]
     */
    public function loadPostActions(Transition $transition): array
    {
        try {
            return [$this->frozen_fields_retriever->getFrozenFields($transition)];
        } catch (NoFrozenFieldsPostActionException $exception) {
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
        $to_transition_id = $post_action->getTransition()->getId();

        return $this->frozen_dao->createPostActionForTransitionId(
            $to_transition_id,
            $post_action->getFieldIds()
        );
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        return $this->frozen_dao->isFieldUsedInPostAction($field->getId());
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition  the template transition
     * @param int        $to_transition_id the id of the transition
     * @param array      $field_mapping    the field mapping
     *
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $postactions = $this->loadPostActions($from_transition);
        foreach ($postactions as $postaction) {
            $from_field_ids = $postaction->getFieldIds();
            $to_field_ids   = [];

            foreach ($field_mapping as $mapping) {
                foreach ($from_field_ids as $from_field_id) {
                    if ($mapping['from'] == $from_field_id) {
                        $to_field_ids[] = $mapping['to'];
                    }
                }
            }

            $this->frozen_dao->createPostActionForTransitionId($to_transition_id, $to_field_ids);
        }
    }

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement  $xml        containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition        $transition to which the postaction is attached
     *
     * @return FrozenFields|null The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        $fields = [];
        foreach ($xml->field_id as $xml_field_id) {
            if (isset($xmlMapping[(string) $xml_field_id['REF']])) {
                $fields[] = $xmlMapping[(string) $xml_field_id['REF']];
            }
        }

        if (count($fields) > 0) {
            return new FrozenFields($transition, 0, $fields);
        }

        return null;
    }
}
