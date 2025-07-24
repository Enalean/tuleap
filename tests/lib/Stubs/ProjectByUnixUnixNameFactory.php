<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use Tuleap\Project\ProjectByUnixNameFactory;

final class ProjectByUnixUnixNameFactory implements ProjectByUnixNameFactory
{
    private array $projects;

    private function __construct(\Project ...$projects)
    {
        foreach ($projects as $project) {
            $this->projects[$project->getUnixName()] = $project;
        }
    }

    public static function buildWithoutProject(): self
    {
        return new self();
    }

    public static function buildWith(\Project ...$projects): self
    {
        return new self(...$projects);
    }

    #[\Override]
    public function getProjectByCaseInsensitiveUnixName(string $unix_name): ?\Project
    {
        if (isset($this->projects[$unix_name]) && $this->isValid($this->projects[$unix_name])) {
            return $this->projects[$unix_name];
        }
        return null;
    }

    private function isValid(\Project $project): bool
    {
        return ! $project->isError() && ! $project->isDeleted();
    }
}
