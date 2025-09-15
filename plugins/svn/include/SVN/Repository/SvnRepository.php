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

namespace Tuleap\SVN\Repository;

use ForgeConfig;
use Project;
use Tuleap\SVN\Repository;

final class SvnRepository implements Repository
{
    private const TO_BE_CREATED_REPOSITORY_ID = -1;

    private function __construct(
        private int $id,
        private readonly string $name,
        private readonly ?string $backup_path,
        private ?int $deletion_date,
        private readonly Project $project,
        private bool $has_default_permissions,
    ) {
    }

    public static function buildToBeCreatedRepository(string $name, Project $project): self
    {
        return new self(self::TO_BE_CREATED_REPOSITORY_ID, $name, null, null, $project, true);
    }

    public static function buildActiveRepository(int $id, string $name, Project $project): self
    {
        return new self($id, $name, null, null, $project, true);
    }

    /**
     * @psalm-param array{id: string, name: string, project_id?: string, is_core?: string, has_default_permissions: string, accessfile_id?: string, repository_deletion_date: string|null, backup_path: string|null} $row
     */
    public static function buildFromDatabase(array $row, Project $project): self
    {
        return new self(
            (int) $row['id'],
            $row['name'],
            $row['backup_path'],
            $row['repository_deletion_date'] !== null ? (int) $row['repository_deletion_date'] : null,
            $project,
            $row['has_default_permissions'] === '1',
        );
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

    /**
     * @psalm-taint-escape shell
     * @psalm-taint-escape file
     */
    #[\Override]
    public function getName(): string
    {
        if (strpos($this->name, DIRECTORY_SEPARATOR) !== false) {
            throw new \RuntimeException('$this->name is not expected to contain a directory separator, got ' . $this->name);
        }
        return $this->name;
    }

    #[\Override]
    public function getProject(): \Project
    {
        return $this->project;
    }

    #[\Override]
    public function getPublicPath(): string
    {
        return '/svnplugin/' . $this->getFullName();
    }

    #[\Override]
    public function getFullName(): string
    {
        return $this->getProject()->getUnixNameMixedCase() . '/' . $this->getName();
    }

    #[\Override]
    public function getSystemPath(): string
    {
        return ForgeConfig::get('sys_data_dir') . '/svn_plugin/' . (int) $this->getProject()->getId() . '/' . $this->getName();
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
        return $this->isRepositoryCreated();
    }

    #[\Override]
    public function getBackupPath(): ?string
    {
        return $this->backup_path;
    }

    #[\Override]
    public function getSystemBackupPath(): string
    {
        return ForgeConfig::get('sys_project_backup_path') . '/svn';
    }

    #[\Override]
    public function getBackupFileName(): string
    {
        return $this->getName() . $this->getDeletionDate() . '.svn';
    }

    #[\Override]
    public function getDeletionDate(): ?int
    {
        return $this->deletion_date;
    }

    #[\Override]
    public function setDeletionDate(int $deletion_date): void
    {
        $this->deletion_date = $deletion_date;
    }

    #[\Override]
    public function isDeleted(): bool
    {
        return ! empty($this->deletion_date);
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
