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

use Tuleap\Artidoc\Document\SaveOneSection;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\UUID;

final class SaveOneSectionStub implements SaveOneSection
{
    /**
     * @var array<int, int>
     */
    private array $saved_before = [];
    /**
     * @var array<int, int>
     */
    private array $saved_end = [];

    private function __construct(private DatabaseUUIDFactory $uuid_factory, private string $id)
    {
    }

    public static function withGeneratedSectionId(DatabaseUUIDFactory $uuid_factory, string $id): self
    {
        return new self($uuid_factory, $id);
    }

    public function isSaved(int $id): bool
    {
        return isset($this->saved_end[$id]) || isset($this->saved_before[$id]);
    }

    public function getSavedEndForId(int $id): int
    {
        return $this->saved_end[$id];
    }

    public function getSavedBeforeForId(int $id): int
    {
        return $this->saved_before[$id];
    }

    public function saveSectionAtTheEnd(int $item_id, int $artifact_id): UUID
    {
        $this->saved_end[$item_id] = $artifact_id;

        return $this->uuid_factory->buildUUIDFromHexadecimalString($this->id);
    }

    public function saveSectionBefore(int $item_id, int $artifact_id, string $sibling_section_id): UUID
    {
        $this->saved_before[$item_id] = $artifact_id;

        return $this->uuid_factory->buildUUIDFromHexadecimalString($this->id);
    }
}
