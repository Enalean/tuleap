<?php
/**
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
 */

namespace Tuleap\Tracker\Test\Builders\Fields;

trait FieldBuilderWithPermissions
{
    /** @var list<\PFUser> $user_with_read_permissions */
    private array $user_with_read_permissions = [];
    /** @var array<int, bool> $read_permissions */
    private array $read_permissions = [];
    /** @var list<\PFUser> */
    private array $user_with_update_permissions = [];
    /** @var array<int, bool> */
    private array $update_permissions = [];
    /** @var list<\PFUser> */
    private array $user_with_submit_permissions = [];
    /** @var array<int, bool> */
    private array $submit_permissions = [];

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

    public function withSubmitPermission(\PFUser $user, bool $user_can_submit): self
    {
        $this->user_with_submit_permissions[]           = $user;
        $this->submit_permissions[(int) $user->getId()] = $user_can_submit;
        return $this;
    }

    private function setPermissions(\Tracker_FormElement $field): void
    {
        foreach ($this->user_with_read_permissions as $user) {
            $field->setUserCanRead($user, $this->read_permissions[(int) $user->getId()]);
        }
        foreach ($this->user_with_update_permissions as $user) {
            $field->setUserCanUpdate($user, $this->update_permissions[(int) $user->getId()]);
        }
        foreach ($this->user_with_submit_permissions as $user) {
            $field->setUserCanSubmit($user, $this->submit_permissions[(int) $user->getId()]);
        }
    }
}
