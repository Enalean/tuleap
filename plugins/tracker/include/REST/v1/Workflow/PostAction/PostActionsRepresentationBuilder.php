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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use EventManager;
use Transition_PostAction;
use Transition_PostAction_CIBuild;
use Transition_PostAction_Field_Date;
use Transition_PostAction_Field_Float;
use Transition_PostAction_Field_Int;
use Tuleap\Tracker\REST\v1\Event\PostActionVisitExternalActionsEvent;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

class PostActionsRepresentationBuilder implements Visitor
{
    /**
     * @var Transition_PostAction[]
     */
    private $post_actions;

    /**
     * @var array
     */
    private $post_action_representations;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager, array $post_actions)
    {
        $this->event_manager               = $event_manager;
        $this->post_actions                = $post_actions;
        $this->post_action_representations = [];
    }

    /**
     * @throws UnsupportedDateValueException
     */
    public function build()
    {
        foreach ($this->post_actions as $post_action) {
            $post_action->accept($this);
        }
        return $this->post_action_representations;
    }

    public function visitCIBuild(Transition_PostAction_CIBuild $post_action)
    {
        $this->post_action_representations[] = RunJobRepresentation::build(
            $post_action->getId(),
            $post_action->getJobUrl()
        );
    }

    /**
     * @throws UnsupportedDateValueException
     */
    public function visitDateField(Transition_PostAction_Field_Date $post_action)
    {
        $this->post_action_representations[] = SetFieldValueRepresentation::forDate(
            $post_action->getId(),
            $post_action->getFieldId(),
            $post_action->getValueType()
        );
    }

    public function visitIntField(Transition_PostAction_Field_Int $post_action)
    {
        $this->post_action_representations[] = SetFieldValueRepresentation::forInt(
            $post_action->getId(),
            $post_action->getFieldId(),
            $post_action->getValue()
        );
    }

    public function visitFloatField(Transition_PostAction_Field_Float $post_action)
    {
        $this->post_action_representations[] = SetFieldValueRepresentation::forFloat(
            $post_action->getId(),
            $post_action->getFieldId(),
            $post_action->getValue()
        );
    }

    public function visitFrozenFields(FrozenFields $frozen_fields)
    {
        $this->post_action_representations[] = FrozenFieldsRepresentation::build(
            $frozen_fields->getId(),
            $frozen_fields->getFieldIds()
        );
    }

    public function visitHiddenFieldsets(HiddenFieldsets $hidden_fieldsets)
    {
        $fieldset_ids = [];
        foreach ($hidden_fieldsets->getFieldsets() as $fieldset) {
            $fieldset_ids[] = (int) $fieldset->getID();
        }

        $this->post_action_representations[] = HiddenFieldsetsRepresentation::build(
            $hidden_fieldsets->getId(),
            $fieldset_ids
        );
    }

    public function visitExternalActions(Transition_PostAction $post_action)
    {
        $event = new PostActionVisitExternalActionsEvent($post_action);
        $this->event_manager->processEvent($event);

        $external_post_action = $event->getRepresentation();
        if ($external_post_action === null) {
            return;
        }

        $this->post_action_representations[] = $external_post_action;
    }
}
