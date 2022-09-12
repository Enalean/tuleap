<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

final class HeartbeatsEntry
{
    public function __construct(private int $updated_at, private string $html_message, private string $icon_name, private ?\PFUser $user = null)
    {
    }

    public function getUpdatedAt(): int
    {
        return $this->updated_at;
    }

    public function getHTMLMessage(): string
    {
        return $this->html_message;
    }

    public function getIconName(): string
    {
        return $this->icon_name;
    }

    public function getUser(): ?\PFUser
    {
        return $this->user;
    }
}
