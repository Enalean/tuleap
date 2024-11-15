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

use Tracker_FormElement_Field_File;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FileFieldBuilder
{
    private string $name = 'file';
    private \Tracker $tracker;
    /** @var list<\PFUser> */
    private array $user_with_read_permission = [];
    /** @var array<int, bool> */
    private array $read_permissions = [];

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(19)->build();
    }

    public static function aFileField(int $id): self
    {
        return new self($id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function withReadPermission(\PFUser $user, bool $user_can_read): self
    {
        $this->user_with_read_permission[]      = $user;
        $this->read_permissions[$user->getId()] = $user_can_read;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_File
    {
        $field = new Tracker_FormElement_Field_File(
            $this->id,
            $this->tracker->getId(),
            15,
            $this->name,
            $this->name,
            '',
            true,
            '',
            false,
            false,
            10,
            null
        );
        $field->setTracker($this->tracker);
        foreach ($this->user_with_read_permission as $user) {
            $field->setUserCanRead($user, $this->read_permissions[$user->getId()]);
        }
        return $field;
    }
}
