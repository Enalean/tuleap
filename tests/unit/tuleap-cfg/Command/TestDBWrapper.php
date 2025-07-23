<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace TuleapCfg\Command;

use PHPUnit\Framework\Assert;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;

final class TestDBWrapper implements DBWrapperInterface
{
    private array $prepared_run     = [];
    public array $statements        = [];
    public array $statements_params = [];

    public function assertContains(string $needle)
    {
        Assert::assertContains($needle, $this->statements);
    }

    public function assertNoStatments()
    {
        Assert::assertEmpty($this->statements);
    }

    public function setRunReturn(string $statement, mixed $value): void
    {
        $this->prepared_run[$statement] = $value;
    }

    #[\Override]
    public function escapeIdentifier(string $identifier, bool $quote = true): string
    {
        if ($quote) {
            return sprintf("'%s'", $identifier);
        }
        return $identifier;
    }

    #[\Override]
    public function rawExec(string $statement): void
    {
        $this->statements[] = $statement;
    }

    #[\Override]
    public function run(string $statement, ...$params)
    {
        $this->statements[]                                    = $statement;
        $this->statements_params[count($this->statements) - 1] = $params;
        return $this->prepared_run[$statement] ?? null;
    }

    #[\Override]
    public function row(string $statement)
    {
        $this->statements[] = $statement;
    }

    #[\Override]
    public function single(string $statement)
    {
        $this->statements[] = $statement;
    }
}
