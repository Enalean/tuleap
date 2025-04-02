<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\TestManagement\Workflow;

use Tuleap\TestManagement\Config;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;

final class PostActionChecker
{
    public function __construct(private Config $config, private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function checkPostActions(CheckPostActionsForTracker $event): void
    {
        $frozen_fields_post_actions    = $event->getPostActions()->getFrozenFieldsPostActions();
        $hidden_fieldsets_post_actions = $event->getPostActions()->getHiddenFieldsetsPostActions();

        if (count($frozen_fields_post_actions) === 0 && count($hidden_fieldsets_post_actions) === 0) {
            return;
        }

        $tracker    = $event->getTracker();
        $tracker_id = $tracker->getId();
        $project    = $tracker->getProject();
        if (
            $tracker_id !== $this->config->getTestExecutionTrackerId($project) &&
            $tracker_id !== $this->config->getTestDefinitionTrackerId($project)
        ) {
            return;
        }

        if (count($hidden_fieldsets_post_actions) > 0) {
            $message = dgettext(
                'tuleap-testmanagement',
                'The post actions cannot be saved because this tracker is used in TestManagement and "hidden fieldsets" are defined.'
            );
            $event->setPostActionsNonEligible();
            $event->setErrorMessage($message);

            return;
        }

        foreach ($frozen_fields_post_actions as $frozen_fields_post_action) {
            foreach ($frozen_fields_post_action->getFieldIds() as $frozen_field_id) {
                $field = $this->form_element_factory->getFieldById($frozen_field_id);
                if ($field instanceof \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField) {
                    $message = dgettext(
                        'tuleap-testmanagement',
                        'The post actions cannot be saved because this tracker is used in TestManagement and "frozen fields" are defined on an artifact link field.'
                    );
                    $event->setPostActionsNonEligible();
                    $event->setErrorMessage($message);
                }
            }
        }
    }
}
