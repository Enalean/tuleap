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

namespace Tuleap\Docman\Log;


/**
 * @psalm-immutable
 */
final class LogEntry
{
    public const int EVENT_ADD                = PLUGIN_DOCMAN_EVENT_ADD;
    public const int EVENT_EDIT               = PLUGIN_DOCMAN_EVENT_EDIT;
    public const int EVENT_MOVE               = PLUGIN_DOCMAN_EVENT_MOVE;
    public const int EVENT_DEL                = PLUGIN_DOCMAN_EVENT_DEL;
    public const int EVENT_DEL_VERSION        = PLUGIN_DOCMAN_EVENT_DEL_VERSION;
    public const int EVENT_ACCESS             = PLUGIN_DOCMAN_EVENT_ACCESS;
    public const int EVENT_NEW_VERSION        = PLUGIN_DOCMAN_EVENT_NEW_VERSION;
    public const int EVENT_METADATA_UPDATE    = PLUGIN_DOCMAN_EVENT_METADATA_UPDATE;
    public const int EVENT_WIKIPAGE_UPDATE    = PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE;
    public const int EVENT_SET_VERSION_AUTHOR = PLUGIN_DOCMAN_EVENT_SET_VERSION_AUTHOR;
    public const int EVENT_SET_VERSION_DATE   = PLUGIN_DOCMAN_EVENT_SET_VERSION_DATE;
    public const int EVENT_RESTORE            = PLUGIN_DOCMAN_EVENT_RESTORE;
    public const int EVENT_RESTORE_VERSION    = PLUGIN_DOCMAN_EVENT_RESTORE_VERSION;
    public const int EVENT_LOCK_ADD           = PLUGIN_DOCMAN_EVENT_LOCK_ADD;
    public const int EVENT_LOCK_DEL           = PLUGIN_DOCMAN_EVENT_LOCK_DEL;

    /**
     * @psalm-param self::EVENT_* $type
     */
    public function __construct(
        public \DateTimeInterface $when,
        public \PFUser $who,
        public string $what,
        public ?string $old_value,
        public ?string $new_value,
        public ?string $diff_link,
        public int $type,
        public ?string $field,
        public int $project_id,
    ) {
    }
}
