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

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

final class OpenListFieldBuilder
{
    private int $field_id = 123;
    private string $name  = 'A field';
    private string $label = 'open_list_field';
    private Tracker $tracker;
    /** @var list<\PFUser> */
    private array $user_with_read_permissions = [];
    /** @var array<int, bool> */
    private array $read_permissions = [];

    private function __construct()
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(3)->build();
    }

    public static function anOpenListField(): self
    {
        return new self();
    }

    public function withId(int $field_id): self
    {
        $this->field_id = $field_id;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function withTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;

        return $this;
    }

    public function withReadPermission(\PFUser $user, bool $user_can_read): self
    {
        $this->user_with_read_permissions[]           = $user;
        $this->read_permissions[(int) $user->getId()] = $user_can_read;

        return $this;
    }

    public function build(): \Tracker_FormElement_Field_OpenList
    {
        $field = new \Tracker_FormElement_Field_OpenList(
            $this->field_id,
            $this->tracker->getId(),
            1,
            $this->name,
            $this->label,
            '',
            true,
            'P',
            false,
            '',
            1
        );

        $field->setTracker($this->tracker);

        foreach ($this->user_with_read_permissions as $user) {
            $field->setUserCanRead($user, $this->read_permissions[(int) $user->getId()]);
        }

        return $field;
    }
}
