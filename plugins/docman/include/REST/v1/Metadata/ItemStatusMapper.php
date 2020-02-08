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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_Item;

class ItemStatusMapper
{

    /**
     * @var \Docman_SettingsBo
     */
    private $docman_settings_bo;
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

    private const LEGACY_ITEM_STATUS_ARRAY_MAP = [
        PLUGIN_DOCMAN_ITEM_STATUS_NONE => self::ITEM_STATUS_NONE,
        PLUGIN_DOCMAN_ITEM_STATUS_DRAFT => self::ITEM_STATUS_DRAFT,
        PLUGIN_DOCMAN_ITEM_STATUS_APPROVED => self::ITEM_STATUS_APPROVED,
        PLUGIN_DOCMAN_ITEM_STATUS_REJECTED => self::ITEM_STATUS_REJECTED,
    ];

    public function __construct(\Docman_SettingsBo $docman_settings_bo)
    {
        $this->docman_settings_bo  = $docman_settings_bo;
    }

    /**
     * @throws HardCodedMetadataException
     */
    public function getItemStatusIdFromItemStatusString(?string $status_string): int
    {
        $this->checkStatusIsAvailable($status_string);

        if ($status_string === null) {
            throw HardCodedMetadataException::itemStatusNullIsInvalid();
        }

        $this->checkStatusExists($status_string);

        return self::ITEM_STATUS_ARRAY_MAP[$status_string];
    }

    /**
     * @throws HardCodedMetadataException
     */
    public function getItemStatusWithParentInheritance(Docman_Item $parent, ?string $status_string): int
    {
        if ($status_string === null) {
            $status_string = self::LEGACY_ITEM_STATUS_ARRAY_MAP[$parent->getStatus()];
        }

        $this->checkStatusIsAvailable($status_string);

        $this->checkStatusExists($status_string);

        return self::ITEM_STATUS_ARRAY_MAP[$status_string];
    }

    /**
     *
     * @throws HardCodedMetadataException
     */
    private function checkStatusIsAvailable(?string $status_string): void
    {
        $metadata_usage = $this->docman_settings_bo->getMetadataUsage('status');
        if (! ($metadata_usage === "1") && ($status_string !== ItemStatusMapper::ITEM_STATUS_NONE)) {
            throw HardCodedMetadataException::itemStatusNotAvailable();
        }
    }

    /**
     * @throws HardCodedMetadataException
     */
    private function checkStatusExists(string $status_string): void
    {
        if (! isset(self::ITEM_STATUS_ARRAY_MAP[$status_string])) {
            throw HardCodedMetadataException::itemStatusIsInvalid($status_string);
        }
    }
}
