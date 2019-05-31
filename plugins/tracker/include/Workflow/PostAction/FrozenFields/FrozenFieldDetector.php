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

use Tracker_Artifact;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class FrozenFieldDetector
{
    /**
     * @var TransitionRetriever
     */
    private $transition_retriever;
    /** @var FrozenFieldsRetriever */
    private $frozen_fields_retriever;

    public function __construct(TransitionRetriever $transition_retriever, FrozenFieldsRetriever $frozen_fields_retriever)
    {
        $this->transition_retriever    = $transition_retriever;
        $this->frozen_fields_retriever = $frozen_fields_retriever;
    }

    public function isFieldFrozen(Tracker_Artifact $artifact, Tracker_FormElement_Field $field): bool
    {
        try {
            $current_state_transition = $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
            $frozen_post_action       = $this->frozen_fields_retriever->getFrozenFields($current_state_transition);
        } catch (NoTransitionForStateException | NoFrozenFieldsPostActionException $e) {
            return false;
        }
        $field_id = (int) $field->getId();

        if (in_array($field_id, $frozen_post_action->getFieldIds())) {
            return true;
        }

        return false;
    }
}
