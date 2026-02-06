<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Git\Tests\Builders;

use Git_Backend_Interface;
use GitDao;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class GitRepositoryTestBuilder
{
    private \Project $project;
    private ?\GitRepository $parent_repository;
    private ?Git_Backend_Interface $backend = null;

    private int $id                  = 809;
    private ?int $migrated_to_gerrit = null;

    private string $namespace     = '';
    private string $name          = 'unfederal_dictation';
    private string $backend_type  = GitDao::BACKEND_GITOLITE;
    private string $path          = 'path/to/repo';
    private string $deletion_date = '0000-00-00 00:00:00';
    private string $scope         = \GitRepository::REPO_SCOPE_PROJECT;

    private function __construct(?\GitRepository $parent_repository)
    {
        $this->project           = ProjectTestBuilder::aProject()->build();
        $this->parent_repository = $parent_repository;
    }

    public static function aProjectRepository(): self
    {
        return new self(null);
    }

    public static function aForkOf(\GitRepository $parent_repository): self
    {
        return new self($parent_repository);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function migratedToGerrit(int $id = 1): self
    {
        $this->migrated_to_gerrit = $id;
        return $this;
    }

    public function inProject(\Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function withBackend(Git_Backend_Interface $backend): self
    {
        $this->backend = $backend;
        return $this;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function withDeletionDate(string $deletion_date): self
    {
        $this->deletion_date = $deletion_date;
        return $this;
    }

    /**
     * @psalm-param \GitRepository::REPO_SCOPE_PROJECT|\GitRepository::REPO_SCOPE_INDIVIDUAL $scope
     * @return $this
     */
    public function withScope(string $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function build(): \GitRepository
    {
        $repository = new \GitRepository();
        $repository->setId($this->id);
        $repository->setProject($this->project);
        $repository->setNamespace($this->namespace);
        $repository->setName($this->name);
        $repository->setRemoteServerId($this->migrated_to_gerrit);
        $repository->setBackendType($this->backend_type);
        $repository->setPath($this->path);
        $repository->setDeletionDate($this->deletion_date);
        $repository->setScope($this->scope);

        if ($this->parent_repository) {
            $repository->setParent($this->parent_repository);
            $repository->setParentId($this->parent_repository->getId());
        }
        if ($this->backend !== null) {
            $repository->setBackend($this->backend);
        }

        return $repository;
    }
}
