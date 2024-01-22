<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVNCore;

interface Repository
{
    public function getSettingUrl(): string;

    public function setId(int $id): void;

    public function getId(): int;

    public function getName(): string;

    public function getProject(): \Project;

    public function getPublicPath(): string;

    public function getFullName(): string;

    public function getSystemPath(): string;

    public function isRepositoryCreated(): bool;

    public function getSvnUrl(): string;

    public function getSvnDomain(): string;

    public function getHtmlPath(): string;

    public function canBeDeleted(): bool;

    public function getBackupPath(): ?string;

    public function getSystemBackupPath(): string;

    public function getBackupFileName(): string;

    public function getDeletionDate(): ?int;

    public function setDeletionDate(int $deletion_date): void;

    public function isDeleted(): bool;

    public function hasDefaultPermissions(): bool;
}
