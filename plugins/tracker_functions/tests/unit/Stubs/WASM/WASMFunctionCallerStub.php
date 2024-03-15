<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Stubs\WASM;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\TrackerFunctions\WASM\WASMFunctionCaller;
use Tuleap\TrackerFunctions\WASM\WASMResponseRepresentation;

final class WASMFunctionCallerStub implements WASMFunctionCaller
{
    private bool $has_been_called = false;

    /**
     * @param Ok<WASMResponseRepresentation>|Err<Fault> $result
     */
    private function __construct(
        private readonly Ok|Err $result,
    ) {
    }

    public static function withOkResult(WASMResponseRepresentation $response): self
    {
        return new self(Result::ok($response));
    }

    public static function withErrResult(string $message): self
    {
        return new self(Result::err(Fault::fromMessage($message)));
    }

    public static function withEmptyErrResult(): self
    {
        return self::withErrResult('');
    }

    public function callWASMFunction(string $wasm_function_path, string $payload): Ok|Err
    {
        $this->has_been_called = true;
        return $this->result;
    }

    public function hasBeenCalled(): bool
    {
        return $this->has_been_called;
    }
}
