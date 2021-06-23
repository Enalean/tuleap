<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

/**
 * @psalm-immutable
 */
final class GitlabReferenceSplittedValues
{
    private ?string $repository_name;
    private ?string $value;

    private function __construct(?string $repository_name, ?string $value)
    {
        $this->repository_name = $repository_name;
        $this->value           = $value;
    }

    public function getRepositoryName(): ?string
    {
        return $this->repository_name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public static function buildNotFoundReference(): self
    {
        return new self(null, null);
    }

    public static function buildFromReference(string $repository_name, string $value): self
    {
        return new self($repository_name, $value);
    }
}
