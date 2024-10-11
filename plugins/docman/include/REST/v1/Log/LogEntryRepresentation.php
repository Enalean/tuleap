<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Log;

use Tuleap\Docman\Log\LogEntry;
use Tuleap\REST\JsonCast;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class LogEntryRepresentation
{
    private function __construct(
        public string $when,
        public UserRepresentation $who,
        public string $what,
        public ?string $old_value,
        public ?string $new_value,
        public ?string $diff_link,
    ) {
    }

    public static function fromEntry(LogEntry $entry, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            JsonCast::fromNotNullDateTimeToDate($entry->when),
            UserRepresentation::build($entry->who, $provide_user_avatar_url),
            $entry->what,
            $entry->old_value,
            $entry->new_value,
            $entry->diff_link,
        );
    }
}
