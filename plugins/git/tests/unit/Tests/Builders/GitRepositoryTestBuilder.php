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

use Tuleap\Test\Builders\ProjectTestBuilder;

final class GitRepositoryTestBuilder
{
    private int $id           = 809;
    private string $namespace = '';
    private string $name      = 'unfederal_dictation';
    private \Project $project;
    private bool $is_migrated_to_gerrit        = false;
    private ?\GitRepository $parent_repository = null;

    private function __construct()
    {
        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public static function aProjectRepository(): self
    {
        return new self();
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

    public function withParentRepository(\GitRepository $repository): self
    {
        $this->parent_repository = $repository;
        return $this;
    }

    public function migratedToGerrit(): self
    {
        $this->is_migrated_to_gerrit = true;
        return $this;
    }

    public function inProject(\Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function build(): \GitRepository
    {
        $repository = new \GitRepository();
        $repository->setId($this->id);
        $repository->setProject($this->project);
        $repository->setNamespace($this->namespace);
        $repository->setName($this->name);

        if ($this->parent_repository) {
            $repository->setParent($this->parent_repository);
        }

        if ($this->is_migrated_to_gerrit) {
            $repository->setRemoteServerId('gerrit-server');
        }

        return $repository;
    }
}
