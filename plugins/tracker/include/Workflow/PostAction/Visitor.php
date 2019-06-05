<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction;

use Transition_PostAction_CIBuild;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;

interface Visitor
{
    public function visitCIBuild(Transition_PostAction_CIBuild $post_action);

    public function visitDateField(\Transition_PostAction_Field_Date $param);

    public function visitIntField(\Transition_PostAction_Field_Int $param);

    public function visitFloatField(\Transition_PostAction_Field_Float $param);

    public function visitFrozenFields(FrozenFields $frozen_fields);

    public function visitHiddenFieldsets(HiddenFieldsets $hidden_fieldsets);
}
