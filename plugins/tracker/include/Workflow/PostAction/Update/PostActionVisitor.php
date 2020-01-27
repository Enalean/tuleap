<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;
use Tuleap\Tracker\Workflow\Update\PostAction;

interface PostActionVisitor
{
    public function visitCIBuildValue(CIBuildValue $ci_build_action);

    public function visitSetDateValue(SetDateValue $set_date_value_action);

    public function visitSetIntValue(SetIntValue $set_int_value_action);

    public function visitSetFloatValue(SetFloatValue $set_float_value_action);

    public function visitFrozenFieldsValue(FrozenFieldsValue $frozen_fields);

    public function visitHiddenFieldsetsValue(HiddenFieldsetsValue $hidden_fieldsets_value);

    public function visitExternalPostActionValue(PostAction $post_action_value);
}
