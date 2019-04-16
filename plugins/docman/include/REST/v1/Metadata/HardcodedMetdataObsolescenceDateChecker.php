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

use Tuleap\Docman\REST\v1\ItemRepresentation;

class HardcodedMetdataObsolescenceDateChecker
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
     * @throws InvalidDateComparisonException
     */
    public function checkDateValidity(int $current_date, int $obsolescence_date, int $item_type): void
    {
        if ($item_type === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            return;
        }

        if (($current_date > $obsolescence_date) && $this->isObsolescenceMetadataUsed()) {
            throw new InvalidDateComparisonException();
        }
    }

    /**
     * @throws ObsoloscenceDateUsageMismatchException
     */
    public function checkObsolescenceDateUsage(?string $date, int $item_type): void
    {
        if ($item_type === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            return;
        }

        if ($date === null) {
            throw new ObsoloscenceDateUsageMismatchException('The date cannot be null');
        }

        if ($date === ItemRepresentation::OBSOLESCENCE_DATE_NONE && $this->isObsolescenceMetadataUsed()) {
            throw new ObsoloscenceDateUsageMismatchException(
                '"obsolescence_date" parameter is required to create a new document.'
            );
        }

        if ($date !== ItemRepresentation::OBSOLESCENCE_DATE_NONE && !$this->isObsolescenceMetadataUsed()) {
            throw new ObsoloscenceDateUsageMismatchException(
                'The project does not support obsolescence date, you should not provide it to create a new document.'
            );
        }
    }

    public function isObsolescenceMetadataUsed(): bool
    {
        $metadata_usage = $this->docman_settings_bo->getMetadataUsage('obsolescence_date');
        return $metadata_usage === '1';
    }
}
