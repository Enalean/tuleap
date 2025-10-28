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

namespace Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\Bind;

use Tuleap\Tracker\FormElement\Field\List\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\List\Bind\ListFieldNullBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\UserGroup\ListFieldUserGroupBind;

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

    #[\Override]
    public function visitListBindStatic(ListFieldStaticBind $bind, BindParameters $parameters): mixed
    {
        return $this->result;
    }

    #[\Override]
    public function visitListBindUsers(ListFieldUserBind $bind, BindParameters $parameters): mixed
    {
        return $this->result;
    }

    #[\Override]
    public function visitListBindUgroups(ListFieldUserGroupBind $bind, BindParameters $parameters): mixed
    {
        return $this->result;
    }

    #[\Override]
    public function visitListBindNull(ListFieldNullBind $bind, BindParameters $parameters): mixed
    {
        return $this->result;
    }
}
