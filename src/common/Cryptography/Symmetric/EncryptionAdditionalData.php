<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography\Symmetric;

/**
 * @psalm-immutable
 */
final readonly class EncryptionAdditionalData
{
    /**
     * @param non-empty-string $table_name
     * @param non-empty-string $field_name
     * @param non-empty-string $id
     * @param list<non-empty-string> $additional_data
     */
    public function __construct(
        private string $table_name,
        private string $field_name,
        private string $id,
        private array $additional_data = [],
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function canonicalize(): string
    {
        $canonicalized_representation = \bin2hex($this->table_name) . '_' . \bin2hex($this->field_name) . '_' . \bin2hex($this->id) . '_';

        return $canonicalized_representation . \implode('_', array_map(fn(string $value): string => \bin2hex($value), $this->additional_data));
    }
}
