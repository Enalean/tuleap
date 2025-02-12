<?php
/*
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

namespace Tuleap\Tracker\Artifact\FormElement\FieldSpecificProperties;

final class SpecificPropertiesWithMappingDuplicator
{
    private array $mapping = [];

    public function __construct(private readonly ?DuplicateSpecificProperties $dao)
    {
    }

    public function duplicate(int $from_field_id, int $to_field_id): void
    {
        if (! $this->dao) {
            return;
        }
        $this->dao->duplicate($from_field_id, $to_field_id);
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function duplicateWithMapping(int $from_field_id, int $to_field_id, array $mapping): void
    {
        if (! $this->dao) {
            return;
        }
        $this->dao->duplicate($from_field_id, $to_field_id);
        $this->mapping = $mapping;
    }
}
