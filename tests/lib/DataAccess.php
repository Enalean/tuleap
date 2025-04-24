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

namespace Tuleap\Test;

use TestHelper;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

final class DataAccess implements LegacyDataAccessInterface
{
    public function query($sql, $params = []): LegacyDataAccessResultInterface
    {
        return TestHelper::emptyDar();
    }

    public function lastInsertId(): bool
    {
        return false;
    }

    public function affectedRows(): int
    {
        return -1;
    }

    public function isError(): string
    {
        return '';
    }

    public function getErrorMessage(): string
    {
        return '';
    }

    public function quoteSmart($value, $params = []): string
    {
        return '';
    }

    public function quoteSmartSchema($value, $params = []): string
    {
        return '';
    }

    public function quoteSmartImplode($glue, $pieces, $params = []): string
    {
        return '';
    }

    public function escapeInt($v, $null = CODENDI_DB_NOT_NULL): string
    {
        return '';
    }

    public function escapeFloat($value): string
    {
        return '';
    }

    public function escapeIntImplode(array $ints): string
    {
        return '';
    }

    public function escapeLikeValue($value): string
    {
        return '';
    }

    public function quoteLikeValueSurround($value): string
    {
        return '';
    }

    public function quoteLikeValueSuffix($value): string
    {
        return '';
    }

    public function quoteLikeValuePrefix($value): string
    {
        return '';
    }

    public function numRows($result): bool
    {
        return false;
    }

    public function fetch($result): array
    {
        return [];
    }

    public function fetchArray($result): array
    {
        return [];
    }

    public function dataSeek($result, $row_number): bool
    {
        return false;
    }

    public function startTransaction(): bool
    {
        return false;
    }

    public function rollback(): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }
}
