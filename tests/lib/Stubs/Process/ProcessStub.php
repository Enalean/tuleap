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

namespace Tuleap\Test\Stubs\Process;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Process\Process;

final readonly class ProcessStub implements Process
{
    /**
     * @param Ok<null>|Err<Fault> $result
     */
    private function __construct(private Ok|Err $result)
    {
    }

    public static function successfulProcess(): self
    {
        return new self(Result::ok(null));
    }

    public static function failingProcess(): self
    {
        return new self(Result::err(Fault::fromMessage('The process failed')));
    }

    #[\Override]
    public function run(): Ok|Err
    {
        return $this->result;
    }

    #[\Override]
    public function getOutput(): string
    {
        return '';
    }

    #[\Override]
    public function getErrorOutput(): string
    {
        return '';
    }
}
