<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders\Fields\List;

use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\DB\DatabaseUUIDV7Factory;

final class ListUserValueBuilder
{
    private string $displayed_name = 'My user';
    private string $user_name      = 'user_name';
    private function __construct(private int $id)
    {
    }

    public static function aUserWithId(int $id): self
    {
        return new self($id);
    }

    public static function noneUser(): self
    {
        return new self(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
    }

    public function withDisplayedName(string $displayed_name): self
    {
        $this->displayed_name = $displayed_name;
        return $this;
    }

    public function withUserName(string $user_name): self
    {
        $this->user_name = $user_name;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_List_Bind_UsersValue
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new Tracker_FormElement_Field_List_Bind_UsersValue($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $this->id, $this->user_name, $this->displayed_name);
    }
}
