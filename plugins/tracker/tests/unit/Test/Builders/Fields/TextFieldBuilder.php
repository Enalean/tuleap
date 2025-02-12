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

use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TextFieldBuilder
{
    private string $name = 'text';
    private \Tracker $tracker;
    /** @var list<\PFUser> $user_with_read_permissions */
    private array $user_with_read_permissions = [];
    /** @var array<int, bool> $read_permissions */
    private array $read_permissions = [];
    /** @var list<\PFUser> */
    private array $user_with_update_permissions = [];
    /** @var array<int, bool> */
    private array $update_permissions = [];
    private bool $is_required         = false;
    private int $number_of_rows       = 0;
    private int $number_of_columns    = 0;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
    }

    public static function aTextField(int $id): self
    {
        return new self($id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withReadPermission(\PFUser $user, bool $user_can_read): self
    {
        $this->user_with_read_permissions[]           = $user;
        $this->read_permissions[(int) $user->getId()] = $user_can_read;

        return $this;
    }

    public function withUpdatePermission(\PFUser $user, bool $user_can_update): self
    {
        $this->user_with_update_permissions[]           = $user;
        $this->update_permissions[(int) $user->getId()] = $user_can_update;
        return $this;
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function withNumberOfRows(int $rows): self
    {
        $this->number_of_rows = $rows;
        return $this;
    }

    public function withNumberOfColumns(int $columns): self
    {
        $this->number_of_columns = $columns;
        return $this;
    }

    private function setProperties(Tracker_FormElement_Field_Text $field): void
    {
        $properties = [];
        if ($this->number_of_rows > 0) {
            $properties['rows'] = ['value' => $this->number_of_rows];
        }
        if ($this->number_of_columns > 0) {
            $properties['cols'] = ['value' => $this->number_of_columns];
        }
        if ($properties !== []) {
            $field->setCacheSpecificProperties($properties);
        }
    }

    public function build(): Tracker_FormElement_Field_Text
    {
        $field = new Tracker_FormElement_Field_Text(
            $this->id,
            10,
            15,
            $this->name,
            '',
            '',
            true,
            'P',
            $this->is_required,
            '',
            10,
            null
        );
        $field->setTracker($this->tracker);
        $this->setProperties($field);
        foreach ($this->user_with_read_permissions as $user) {
            $field->setUserCanRead($user, $this->read_permissions[(int) $user->getId()]);
        }
        foreach ($this->user_with_update_permissions as $user) {
            $field->setUserCanUpdate($user, $this->update_permissions[(int) $user->getId()]);
        }

        return $field;
    }
}
