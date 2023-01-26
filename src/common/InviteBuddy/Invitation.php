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

/**
 * @psalm-immutable
 */
final class Invitation
{
    public const STATUS_CREATING = 'creating';
    public const STATUS_SENT     = 'sent';
    public const STATUS_USED     = 'used';
    public const STATUS_ERROR    = 'error';

    /**
     * @param self::STATUS_* $status
     */
    public function __construct(
        public int $id,
        public string $to_email,
        public ?int $to_user_id,
        public int $from_user_id,
        public ?int $created_user_id,
        public string $status,
        public int $created_on,
        public ?int $to_project_id,
    ) {
    }
}
