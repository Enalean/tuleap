<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use ForgeConfig;
use Project;
use Tuleap\SVN\Repository;

final class CoreRepository implements Repository
{
    public const TO_BE_CREATED_REPOSITORY_ID = -1;

    private function __construct(private readonly \Project $project, private int $id, private bool $has_default_permissions)
    {
    }

    /**
     * @psalm-param array{id: string, name?: string, project_id?: string, is_core?: string, has_default_permissions: string, accessfile_id?: string, repository_deletion_date?: string|null, backup_path?: string|null} $row
     */
    public static function buildActiveRepository(array $row, Project $project): self
    {
        return new self($project, (int) $row['id'], $row['has_default_permissions'] === '1');
    }

    public static function buildToBeCreatedRepository(Project $project): self
    {
        return new self($project, self::TO_BE_CREATED_REPOSITORY_ID, true);
    }

    #[\Override]
    public function getSettingUrl(): string
    {
        return SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => $this->project->getID(),
                'action'   => 'settings',
                'repo_id'  => $this->id,
            ]
        );
    }

    #[\Override]
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->project->getUnixNameMixedCase();
    }

    #[\Override]
    public function getProject(): \Project
    {
        return $this->project;
    }

    #[\Override]
    public function getPublicPath(): string
    {
        return '/svnroot/' . $this->getName();
    }

    #[\Override]
    public function getFullName(): string
    {
        return $this->project->getUnixNameMixedCase();
    }

    #[\Override]
    public function getSystemPath(): string
    {
        return rtrim(ForgeConfig::get('svn_prefix'), '/') . '/' . $this->getName();
    }

    #[\Override]
    public function isRepositoryCreated(): bool
    {
        return is_dir($this->getSystemPath());
    }

    #[\Override]
    public function getSvnUrl(): string
    {
        return $this->getSvnDomain() . $this->getPublicPath();
    }

    #[\Override]
    public function getSvnDomain(): string
    {
        // Domain name must be lowercase (issue with some SVN clients)
        return strtolower(\Tuleap\ServerHostname::HTTPSUrl());
    }

    #[\Override]
    public function getHtmlPath(): string
    {
        return SVN_BASE_URL . '/?' . http_build_query(
            [
                'roottype' => 'svn',
                'root' => $this->getFullName(),
            ]
        );
    }

    #[\Override]
    public function canBeDeleted(): bool
    {
        return false;
    }

    #[\Override]
    public function getBackupPath(): ?string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    #[\Override]
    public function getSystemBackupPath(): string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    #[\Override]
    public function getBackupFileName(): string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    #[\Override]
    public function getDeletionDate(): ?int
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    #[\Override]
    public function setDeletionDate(int $deletion_date): void
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    #[\Override]
    public function isDeleted(): bool
    {
        return false;
    }

    #[\Override]
    public function hasDefaultPermissions(): bool
    {
        return $this->has_default_permissions;
    }

    #[\Override]
    public function setDefaultPermissions(bool $use_it): void
    {
        $this->has_default_permissions = $use_it;
    }
}
