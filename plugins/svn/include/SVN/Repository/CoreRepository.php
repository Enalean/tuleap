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
use Tuleap\SVNCore\Repository;

final class CoreRepository implements Repository
{
    public const TO_BE_CREATED_REPOSITORY_ID = -1;

    /**
     * @var \Project
     */
    private $project;
    /**
     * @var int
     */
    private $id;

    private function __construct(\Project $project, int $repository_id)
    {
        $this->project = $project;
        $this->id      = $repository_id;
    }

    public static function buildActiveRepository(Project $project, int $repository_id): self
    {
        return new self($project, $repository_id);
    }

    public static function buildToBeCreatedRepository(Project $project): self
    {
        return new self($project, self::TO_BE_CREATED_REPOSITORY_ID);
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

    public function getName(): string
    {
        return $this->project->getUnixNameMixedCase();
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function getPublicPath(): string
    {
        return '/svnroot/' . $this->getName();
    }

    public function getFullName(): string
    {
        return $this->project->getUnixNameMixedCase();
    }

    public function getSystemPath(): string
    {
        return rtrim(ForgeConfig::get('svn_prefix'), '/') . '/' . $this->getName();
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
        return false;
    }

    public function getBackupPath(): ?string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    public function getSystemBackupPath(): string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    public function getBackupFileName(): string
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    public function getDeletionDate(): ?int
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    public function setDeletionDate(int $deletion_date): void
    {
        throw new \RuntimeException('Cannot delete a core repository yet');
    }

    public function isDeleted(): bool
    {
        return false;
    }
}
