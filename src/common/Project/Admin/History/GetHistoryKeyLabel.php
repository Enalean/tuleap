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

namespace Tuleap\Project\Admin\History;

use Tuleap\Event\Dispatchable;
use Tuleap\InviteBuddy\InvitationHistoryEntry;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectAdminHistoryEntry;

final class GetHistoryKeyLabel implements Dispatchable
{
    public const NAME = 'getHistoryKeyLabel';

    private ?string $label = null;

    public function __construct(private string $key)
    {
        $invitation_entry = InvitationHistoryEntry::tryFrom($key);
        if ($invitation_entry) {
            $this->label = $invitation_entry->getLabel();
        }

        $project_admin_entry = ProjectAdminHistoryEntry::tryFrom($key);
        if ($project_admin_entry) {
            $this->label = $project_admin_entry->getLabel();
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
