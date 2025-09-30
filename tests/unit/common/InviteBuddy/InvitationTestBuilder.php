<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

final class InvitationTestBuilder
{
    private string $to_email        = 'jdoe@example.com';
    private ?int $to_user_id        = null;
    private int $from_user_id       = 101;
    private int $created_on         = 1234567890;
    private ?int $created_user_id   = null;
    private ?int $to_project_id     = null;
    private ?string $custom_message = null;

    /**
     * @param Invitation::STATUS_* $status
     */
    private function __construct(private int $id, private string $status)
    {
    }

    public static function aSentInvitation(int $id): self
    {
        return new self($id, Invitation::STATUS_SENT);
    }

    public static function aCompletedInvitation(int $id): self
    {
        return new self($id, Invitation::STATUS_COMPLETED);
    }

    public static function aUsedInvitation(int $id): self
    {
        return new self($id, Invitation::STATUS_USED);
    }

    public static function aCreatingInvitation(int $id): self
    {
        return new self($id, Invitation::STATUS_CREATING);
    }

    public static function anErrorInvitation(int $id): self
    {
        return new self($id, Invitation::STATUS_CREATING);
    }

    public function withId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function to(int|string $value): self
    {
        if (is_string($value)) {
            $this->to_email   = $value;
            $this->to_user_id = null;
        } else {
            $this->to_email   = '';
            $this->to_user_id = $value;
        }

        return $this;
    }

    public function from(int $user_id): self
    {
        $this->from_user_id = $user_id;

        return $this;
    }

    public function withCreatedUserId(int $user_id): self
    {
        $this->created_user_id = $user_id;

        return $this;
    }

    public function withCreatedOn(int $created_on): self
    {
        $this->created_on = $created_on;

        return $this;
    }

    public function toProjectId(int $project_id): self
    {
        $this->to_project_id = $project_id;

        return $this;
    }

    public function withCustomMessage(string $custom_message): self
    {
        $this->custom_message = $custom_message;

        return $this;
    }

    public function build(): Invitation
    {
        return new Invitation(
            $this->id,
            $this->to_email,
            $this->to_user_id,
            $this->from_user_id,
            $this->created_user_id,
            $this->status,
            $this->created_on,
            $this->to_project_id,
            $this->custom_message,
        );
    }
}
