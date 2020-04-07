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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\FrozenFieldsValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\HiddenFieldsetsValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueValidator;
use Tuleap\Tracker\Workflow\Update\PostAction;

/**
 * Consistent set of actions
 */
class PostActionCollection implements PostActionVisitor
{
    /**
     * @var CIBuildValue[]
     */
    private $ci_build_actions = [];

    /**
     * @var SetDateValue[]
     */
    private $set_date_value_actions = [];

    /**
     * @var SetIntValue[]
     */
    private $set_int_value_actions = [];

    /**
     * @var SetFloatValue[]
     */
    private $set_float_value_actions = [];

    /**
     * @var FrozenFieldsValue[]
     */
    private $frozen_fields_actions = [];

    /**
     * @var HiddenFieldsetsValue[]
     */
    private $hidden_fieldsets_actions = [];

    /**
     * @var PostAction[]
     */
    private $external_post_actions = [];

    public function __construct(PostAction ...$actions)
    {
        foreach ($actions as $action) {
            $action->accept($this);
        }
    }

    public function visitFrozenFieldsValue(FrozenFieldsValue $frozen_fields_action)
    {
        $this->frozen_fields_actions[] = $frozen_fields_action;
    }

    public function visitCIBuildValue(CIBuildValue $ci_build_action)
    {
        $this->ci_build_actions[] = $ci_build_action;
    }

    public function visitSetDateValue(SetDateValue $set_date_value_action)
    {
        $this->set_date_value_actions[] = $set_date_value_action;
    }

    public function visitSetIntValue(SetIntValue $set_int_value_action)
    {
        $this->set_int_value_actions[] = $set_int_value_action;
    }

    public function visitSetFloatValue(SetFloatValue $set_float_value_action)
    {
        $this->set_float_value_actions[] = $set_float_value_action;
    }

    public function visitHiddenFieldsetsValue(HiddenFieldsetsValue $hidden_fieldsets_value)
    {
        $this->hidden_fieldsets_actions[] = $hidden_fieldsets_value;
    }

    public function visitExternalPostActionValue(PostAction $post_action_value)
    {
        $this->external_post_actions[] = $post_action_value;
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateFrozenFieldsActions(FrozenFieldsValueValidator $validator, \Tracker $tracker): void
    {
        $validator->validate($tracker, ...$this->frozen_fields_actions);
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateHiddenFieldsetsActions(HiddenFieldsetsValueValidator $validator, \Tracker $tracker): void
    {
        $validator->validate($tracker, ...$this->hidden_fieldsets_actions);
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateCIBuildActions(CIBuildValueValidator $validator): void
    {
        $validator->validate(...$this->ci_build_actions);
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateSetDateValueActions(SetDateValueValidator $validator, \Tracker $tracker): void
    {
        $validator->validate($tracker, ...$this->set_date_value_actions);
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateSetIntValueActions(SetIntValueValidator $validator, \Tracker $tracker): void
    {
        $validator->validate($tracker, ...$this->set_int_value_actions);
    }

    /**
     * @throws Internal\InvalidPostActionException
     */
    public function validateSetFloatValueActions(SetFloatValueValidator $validator, \Tracker $tracker): void
    {
        $validator->validate($tracker, ...$this->set_float_value_actions);
    }

    public function getFrozenFieldsPostActions(): array
    {
        return $this->frozen_fields_actions;
    }

    public function getHiddenFieldsetsPostActions(): array
    {
        return $this->hidden_fieldsets_actions;
    }

    public function getCIBuildPostActions(): array
    {
        return $this->ci_build_actions;
    }

    public function getSetDateValuePostActions(): array
    {
        return $this->set_date_value_actions;
    }

    public function getSetIntValuePostActions(): array
    {
        return $this->set_int_value_actions;
    }

    public function getSetFloatValuePostActions(): array
    {
        return $this->set_float_value_actions;
    }

    public function getExternalPostActionsValue(): array
    {
        return $this->external_post_actions;
    }
}
