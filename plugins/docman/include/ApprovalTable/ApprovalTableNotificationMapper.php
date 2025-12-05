<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ApprovalTable;

use Exception;

final class ApprovalTableNotificationMapper
{
    /**
     * @throws Exception
     *
     * @psalm-mutation-free
     */
    public function getNotificationStringNotTranslatedFromNotificationType(int $notification_type): string
    {
        return match ($notification_type) {
            PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED   => 'disabled',
            PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE  => 'all_at_once',
            PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL => 'sequential',
            default                                 => throw new Exception(
                sprintf(
                    'Approval table notification type %s does not match a valid type.',
                    $notification_type,
                ),
            ),
        };
    }
}
