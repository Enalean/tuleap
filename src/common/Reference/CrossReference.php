<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
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

namespace Tuleap\Reference;

require_once __DIR__ . '/../../www/include/utils.php';

class CrossReference
{
    private $sourceUrl              = '';
    private $targetUrl              = '';
    public string $sourceKey        = '';
    public string $insertSourceType = '';
    public string $targetKey        = '';
    public string $insertTargetType = '';

    public function __construct(
        public readonly int|string $refSourceId,
        public readonly int $refSourceGid,
        public readonly string $refSourceType,
        public readonly string $refSourceKey,
        public readonly int|string $refTargetId,
        public readonly int $refTargetGid,
        public readonly string $refTargetType,
        public readonly string $refTargetKey,
        public readonly int|string $userId,
    ) {
        $this->sourceKey        = $refSourceKey;
        $this->insertSourceType = $refSourceType;
        $this->targetKey        = $refTargetKey;
        $this->insertTargetType = $refTargetType;

        $this->computeUrls();
    }

    /** Accessors */
    public function getRefSourceId(): int|string
    {
        return $this->refSourceId;
    }

    public function getRefSourceGid(): int
    {
        return $this->refSourceGid;
    }

    public function getRefSourceType(): string
    {
        return $this->refSourceType;
    }

    public function getRefTargetId(): int|string
    {
        return $this->refTargetId;
    }

    public function getRefTargetGid(): int
    {
        return $this->refTargetGid;
    }

    public function getRefTargetType(): string
    {
        return $this->refTargetType;
    }

    public function getUserId(): int|string
    {
        return $this->userId;
    }

    public function getRefTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getRefSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getRefSourceKey(): string
    {
        return $this->sourceKey;
    }

    public function getRefTargetKey(): string
    {
        return $this->targetKey;
    }

    public function getInsertSourceType(): string
    {
        return $this->insertSourceType;
    }

    public function getInsertTargetType(): string
    {
        return $this->insertTargetType;
    }

    public function isCrossReferenceWith(CrossReference $crossref): bool
    {
        return $this->getRefSourceId() === $crossref->getRefTargetId() &&
            $this->getRefSourceGid() === $crossref->getRefTargetGid() &&
            $this->getRefSourceType() === $crossref->getRefTargetType() &&
            $crossref->getRefSourceId() === $this->getRefTargetId() &&
            $crossref->getRefSourceGid() === $this->getRefTargetGid() &&
            $crossref->getRefSourceType() === $this->getRefTargetType();
    }

    public function computeUrls(): void
    {
        $server_url  = \Tuleap\ServerHostname::HTTPSUrl();
        $group_param = '';
        if ($this->refTargetGid !== 100) {
            $group_param = "&group_id=" . $this->refTargetGid;
        }
        $this->targetUrl = $server_url . "/goto?key=" . urlencode($this->targetKey) . "&val=" . urlencode((string) $this->refTargetId) . $group_param;
        $group_param     = '';
        if ($this->refSourceGid !== 100) {
            $group_param = "&group_id=" . $this->refSourceGid;
        }
        $this->sourceUrl = $server_url . "/goto?key=" . urlencode($this->sourceKey) . "&val=" . urlencode((string) $this->refSourceId) . $group_param;
    }
}
