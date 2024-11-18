<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;

final readonly class BindVisitorStub implements BindVisitor
{
    private function __construct(
        private mixed $result,
    ) {
    }

    public static function build(mixed $result): self
    {
        return new self($result);
    }

    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind, BindParameters $parameters)
    {
        return $this->result;
    }

    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters)
    {
        return $this->result;
    }

    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters)
    {
        return $this->result;
    }

    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind, BindParameters $parameters)
    {
        return $this->result;
    }
}
