<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

class ItemStatusMapper
{
    public const ITEM_STATUS_NONE     = 'none';
    public const ITEM_STATUS_DRAFT    = 'draft';
    public const ITEM_STATUS_APPROVED = 'approved';
    public const ITEM_STATUS_REJECTED = 'rejected';

    private const ITEM_STATUS_ARRAY_MAP = [
        self::ITEM_STATUS_NONE     => PLUGIN_DOCMAN_ITEM_STATUS_NONE,
        self::ITEM_STATUS_DRAFT    => PLUGIN_DOCMAN_ITEM_STATUS_DRAFT,
        self::ITEM_STATUS_APPROVED => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
        self::ITEM_STATUS_REJECTED => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
    ];

    /**
     * @throws StatusNotFoundException
     */
    public function getItemStatusIdFromItemStatusString(?string $status_string): int
    {
        if ($status_string === null) {
            throw new StatusNotFoundException('null is not a valid item status');
        }

        if (! isset(self::ITEM_STATUS_ARRAY_MAP[$status_string])) {
            throw new StatusNotFoundException(
                sprintf(
                    'The status %s is invalid.',
                    $status_string
                )
            );
        }

        return self::ITEM_STATUS_ARRAY_MAP[$status_string];
    }
}
