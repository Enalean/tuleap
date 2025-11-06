<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders\Fields\List;

use PFUser;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBindValue;

final class ListUserBindBuilder
{
    /**
     * @var ListFieldUserBindValue[]
     */
    private array $bind_values;

    private function __construct(private readonly \Tuleap\Tracker\FormElement\Field\List\ListField $field)
    {
    }

    public static function aUserBind(\Tuleap\Tracker\FormElement\Field\List\ListField $field): self
    {
        return new self($field);
    }

    /**
     * @psalm-param PFUser[] $user_list
     */
    public function withUsers(array $user_list): self
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        foreach ($user_list as $user) {
            $bind_value = \Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBindValue::fromUser($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $user, $user->getUserName());

            $this->bind_values[] = $bind_value;
        }

        return $this;
    }

    public function build(): \Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBind
    {
        $bind = new \Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBind(
            new DatabaseUUIDV7Factory(),
            $this->field,
            '',
            [],
            [],
        );
        $this->field->setBind($bind);

        return $bind;
    }
}
