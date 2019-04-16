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

class HardcodedMetadataUsageChecker
{
    /**
     * @var \Docman_SettingsBo
     */
    private $docman_settings_bo;

    public function __construct(\Docman_SettingsBo $docman_settings_bo)
    {
        $this->docman_settings_bo = $docman_settings_bo;
    }

    /**
     * @throws ItemStatusUsageMismatchException
     */
    public function checkStatusIsNotSetWhenStatusMetadataIsNotAllowed(string $status): void
    {
        $metadata_usage = $this->docman_settings_bo->getMetadataUsage('status');
        if (!($metadata_usage === "1") && ($status !== ItemStatusMapper::ITEM_STATUS_NONE)) {
            throw new ItemStatusUsageMismatchException();
        }
    }

    /**
     * @throws StatusNotFoundException
     */
    public function checkItemStatusAuthorisedValue(string $status_string): void
    {
        if (!isset(ItemStatusMapper::ITEM_STATUS_ARRAY_MAP[$status_string])) {
            throw new StatusNotFoundException(
                sprintf(
                    'The status %s is invalid.',
                    $status_string
                )
            );
        }
    }
}
