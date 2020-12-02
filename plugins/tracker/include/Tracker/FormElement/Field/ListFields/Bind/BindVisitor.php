<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;

interface BindVisitor
{
    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind, BindParameters $parameters);
    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters);
    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters);
    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind, BindParameters $parameters);
}
