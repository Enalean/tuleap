<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;

/**
 * I hold the post-creation context for a Changeset. I hold the import configuration,
 * and I tell whether to send notifications.
 * @psalm-immutable
 */
final class PostCreationContext
{
    private function __construct(private TrackerImportConfig $import_config, private bool $send_notifications)
    {
    }

    public static function withConfig(TrackerImportConfig $import_config, bool $send_notifications): self
    {
        return new self($import_config, $send_notifications);
    }

    public static function withNoConfig(bool $send_notifications): self
    {
        return new self(new TrackerNoXMLImportLoggedConfig(), $send_notifications);
    }

    public function getImportConfig(): TrackerImportConfig
    {
        return $this->import_config;
    }

    public function shouldSendNotifications(): bool
    {
        return $this->send_notifications;
    }
}
