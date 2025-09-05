<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb;

/**
 * @psalm-immutable
 */
final readonly class VariableInteger implements Variable
{
    private function __construct(private string $name_in_file, private string $name_in_db, private int $default_value)
    {
    }

    public static function withSameName(string $name, int $default_value): self
    {
        return new self($name, $name, $default_value);
    }

    public static function withNewName(string $name_in_file, string $name_in_db, int $default_value): self
    {
        return new self($name_in_file, $name_in_db, $default_value);
    }

    #[\Override]
    public function getNameInFile(): string
    {
        return $this->name_in_file;
    }

    #[\Override]
    public function getNameInDb(): string
    {
        return $this->name_in_db;
    }

    #[\Override]
    public function getValueAsString(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $this->default_value;
        }
        return (string) $value;
    }
}
