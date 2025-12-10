<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

use Luracast\Restler\RestException;
use Tuleap\REST\I18NRestException;

final class ApprovalTableStatusMapper
{
    /**
     * @throws RestException
     */
    public static function fromStringToConstant(string $status): int
    {
        return match ($status) {
            'closed'   => PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED,
            'disabled' => PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED,
            'enabled'  => PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED,
            'deleted'  => PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED,
            default    => throw new I18NRestException(400, sprintf(
                dgettext('tuleap-docman', 'Unknown table status "%s"'),
                $status,
            )),
        };
    }
}
