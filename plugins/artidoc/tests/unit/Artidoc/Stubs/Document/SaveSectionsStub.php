<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Document;

use Tuleap\Artidoc\Document\SaveSections;

final class SaveSectionsStub implements SaveSections
{
    /**
     * @var array<int, int[]>
     */
    private array $saved = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function save(int $id, array $artifact_ids): void
    {
        $this->saved[$id] = $artifact_ids;
    }

    public function isSaved(int $id): bool
    {
        return isset($this->saved[$id]);
    }

    public function getSavedForId(int $id): array
    {
        return $this->saved[$id];
    }
}
