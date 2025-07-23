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
    #[\Override]
    public function query($sql, $params = []): LegacyDataAccessResultInterface
    {
        return TestHelper::emptyDar();
    }

    #[\Override]
    public function lastInsertId(): bool
    {
        return false;
    }

    #[\Override]
    public function affectedRows(): int
    {
        return -1;
    }

    #[\Override]
    public function isError(): string
    {
        return '';
    }

    #[\Override]
    public function getErrorMessage(): string
    {
        return '';
    }

    #[\Override]
    public function quoteSmart($value, $params = []): string
    {
        return '';
    }

    #[\Override]
    public function quoteSmartSchema($value, $params = []): string
    {
        return '';
    }

    #[\Override]
    public function quoteSmartImplode($glue, $pieces, $params = []): string
    {
        return '';
    }

    #[\Override]
    public function escapeInt($v, $null = CODENDI_DB_NOT_NULL): string
    {
        return '';
    }

    #[\Override]
    public function escapeFloat($value): string
    {
        return '';
    }

    #[\Override]
    public function escapeIntImplode(array $ints): string
    {
        return '';
    }

    #[\Override]
    public function escapeLikeValue($value): string
    {
        return '';
    }

    #[\Override]
    public function quoteLikeValueSurround($value): string
    {
        return '';
    }

    #[\Override]
    public function quoteLikeValueSuffix($value): string
    {
        return '';
    }

    #[\Override]
    public function quoteLikeValuePrefix($value): string
    {
        return '';
    }

    #[\Override]
    public function numRows($result): bool
    {
        return false;
    }

    #[\Override]
    public function fetch($result): array
    {
        return [];
    }

    #[\Override]
    public function fetchArray($result): array
    {
        return [];
    }

    #[\Override]
    public function dataSeek($result, $row_number): bool
    {
        return false;
    }

    #[\Override]
    public function startTransaction(): bool
    {
        return false;
    }

    #[\Override]
    public function rollback(): bool
    {
        return false;
    }

    #[\Override]
    public function commit(): bool
    {
        return false;
    }
}
