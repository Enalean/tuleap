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

final class ListFieldBuilder
{
    private string $label     = 'A list field';
    private string $name      = 'list';
    private bool $is_required = false;
    private bool $is_multiple = false;
    private \Tracker $tracker;
    /** @var list<\PFUser> */
    private array $user_with_read_permissions = [];
    /** @var array<int, bool> */
    private array $read_permissions = [];

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
    }

    public static function aListField(int $id): self
    {
        return new self($id);
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withMultipleValues(): self
    {
        $this->is_multiple = true;
        return $this;
    }

    public function withReadPermission(\PFUser $user, bool $user_can_read): self
    {
        $this->user_with_read_permissions[]     = $user;
        $this->read_permissions[$user->getId()] = $user_can_read;

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

    public function build(): \Tracker_FormElement_Field_Selectbox | \Tracker_FormElement_Field_MultiSelectbox
    {
        $selectbox = $this->buildSelectBox();
        $selectbox->setTracker($this->tracker);

        foreach ($this->user_with_read_permissions as $user) {
            $selectbox->setUserCanRead($user, $this->read_permissions[$user->getId()]);
        }

        return $selectbox;
    }

    private function buildSelectBox(): \Tracker_FormElement_Field_Selectbox | \Tracker_FormElement_Field_MultiSelectbox
    {
        if ($this->is_multiple) {
            $field = new \Tracker_FormElement_Field_MultiSelectbox(
                $this->id,
                $this->tracker->getId(),
                15,
                $this->name,
                $this->label,
                '',
                true,
                'P',
                $this->is_required,
                '',
                10,
                null
            );
            return $field;
        }
        $field = new \Tracker_FormElement_Field_Selectbox(
            $this->id,
            $this->tracker->getId(),
            15,
            $this->name,
            $this->label,
            '',
            true,
            'P',
            $this->is_required,
            '',
            10,
            null
        );
        return $field;
    }
}
