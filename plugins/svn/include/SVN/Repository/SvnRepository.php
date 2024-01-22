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
use Tuleap\SVNCore\Repository;

final class SvnRepository implements Repository
{
    private const TO_BE_CREATED_REPOSITORY_ID = -1;

    private function __construct(
        private int $id,
        private readonly string $name,
        private readonly ?string $backup_path,
        private ?int $deletion_date,
        private readonly Project $project,
        private readonly bool $has_default_permissions,
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

    public static function buildFromDatabase(array $row, Project $project): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            $row['backup_path'],
            $row['repository_deletion_date'] !== null ? (int) $row['repository_deletion_date'] : null,
            $project,
            true,
        );
    }

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

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @psalm-taint-escape shell
     * @psalm-taint-escape file
     */
    public function getName(): string
    {
        if (strpos($this->name, DIRECTORY_SEPARATOR) !== false) {
            throw new \RuntimeException('$this->name is not expected to contain a directory separator, got ' . $this->name);
        }
        return $this->name;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function getPublicPath(): string
    {
        return '/svnplugin/' . $this->getFullName();
    }

    public function getFullName(): string
    {
        return $this->getProject()->getUnixNameMixedCase() . '/' . $this->getName();
    }

    public function getSystemPath(): string
    {
        return ForgeConfig::get('sys_data_dir') . '/svn_plugin/' . (int) $this->getProject()->getId() . '/' . $this->getName();
    }

    public function isRepositoryCreated(): bool
    {
        return is_dir($this->getSystemPath());
    }

    public function getSvnUrl(): string
    {
        return $this->getSvnDomain() . $this->getPublicPath();
    }

    public function getSvnDomain(): string
    {
        // Domain name must be lowercase (issue with some SVN clients)
        return strtolower(\Tuleap\ServerHostname::HTTPSUrl());
    }

    public function getHtmlPath(): string
    {
        return SVN_BASE_URL . '/?' . http_build_query(
            [
                'roottype' => 'svn',
                'root' => $this->getFullName(),
            ]
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->isRepositoryCreated();
    }

    public function getBackupPath(): ?string
    {
        return $this->backup_path;
    }

    public function getSystemBackupPath(): string
    {
        return ForgeConfig::get('sys_project_backup_path') . '/svn';
    }

    public function getBackupFileName(): string
    {
        return $this->getName() . $this->getDeletionDate() . '.svn';
    }

    public function getDeletionDate(): ?int
    {
        return $this->deletion_date;
    }

    public function setDeletionDate(int $deletion_date): void
    {
        $this->deletion_date = $deletion_date;
    }

    public function isDeleted(): bool
    {
        return ! empty($this->deletion_date);
    }

    public function hasDefaultPermissions(): bool
    {
        return $this->has_default_permissions;
    }
}
