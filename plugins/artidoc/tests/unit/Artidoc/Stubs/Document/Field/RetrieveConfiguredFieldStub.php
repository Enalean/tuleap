<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Document\Field;

use Tuleap\Artidoc\Document\Field\RetrieveConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;

final readonly class RetrieveConfiguredFieldStub implements RetrieveConfiguredField
{
    /**
     * @param list<ArtifactSectionField> $rows
     */
    private function __construct(private array $rows)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withConfiguredFields(ArtifactSectionField $first_field, ArtifactSectionField ...$other_fields): self
    {
        return new self([$first_field, ...$other_fields]);
    }

    public static function withoutConfiguredFields(): self
    {
        return new self([]);
    }

    public function retrieveConfiguredFieldsFromItemId(int $item_id): array
    {
        return $this->rows;
    }

    public function retrieveConfiguredFieldsFromSectionId(SectionIdentifier $section_identifier): array
    {
        return $this->rows;
    }
}
