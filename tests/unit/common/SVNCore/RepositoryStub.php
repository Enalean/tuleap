<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore;

use Project;

final class RepositoryStub implements Repository
{
    private bool $has_default_permissions = true;
    private ?string $system_path          = null;

    private function __construct(private readonly Project $project)
    {
    }

    public static function buildSelf(Project $project): self
    {
        return new self($project);
    }

    public function withoutDefaultPermissions(): self
    {
        $new                          = clone $this;
        $new->has_default_permissions = false;
        return $new;
    }

    public function withSystemPath(string $path): self
    {
        $new              = clone $this;
        $new->system_path = $path;
        return $new;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getSettingUrl(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function setId(int $id): void
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getId(): int
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getName(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getPublicPath(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getFullName(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getSystemPath(): string
    {
        if ($this->system_path !== null) {
            return $this->system_path;
        }
        throw new \LogicException('Stub must deal with this case');
    }

    public function isRepositoryCreated(): bool
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getSvnUrl(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getSvnDomain(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getHtmlPath(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function canBeDeleted(): bool
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getBackupPath(): ?string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getSystemBackupPath(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getBackupFileName(): string
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function getDeletionDate(): ?int
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function setDeletionDate(int $deletion_date): void
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function isDeleted(): bool
    {
        throw new \LogicException('Stub must deal with this case');
    }

    public function hasDefaultPermissions(): bool
    {
        return $this->has_default_permissions;
    }
}
