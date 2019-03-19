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

namespace Tuleap\Tracker\Workflow\PostAction\ReadOnly;

use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class ReadOnlyFieldDetector
{
    /** @var PostActionsRetriever */
    private $post_actions_retriever;

    public function __construct(PostActionsRetriever $post_actions_retriever)
    {
        $this->post_actions_retriever = $post_actions_retriever;
    }

    public function isFieldReadOnly(\Tracker_Artifact $artifact, \Tracker_FormElement_Field $field): bool
    {
        $workflow = $artifact->getWorkflow();

        if ($workflow === null || ! $workflow->isUsed() || $workflow->isAdvanced()) {
            return false;
        }

        try {
            $current_state_transition     = $workflow->getFirstTransitionForCurrentState($artifact);
            $read_only_fields_post_action = $this->post_actions_retriever->getReadOnlyFields($current_state_transition);
        } catch (NoTransitionForStateException | NoReadOnlyFieldsPostActionException $e) {
            return false;
        }
        $field_id = (int) $field->getId();

        if (in_array($field_id, $read_only_fields_post_action->getFieldIds())) {
            return true;
        }

        return false;
    }
}
