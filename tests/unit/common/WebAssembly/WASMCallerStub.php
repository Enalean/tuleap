<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAssembly;

use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final class WASMCallerStub implements WASMCaller
{
    private bool $has_been_called = false;

    /**
     * @psalm-param Option<Result<string, Fault>> $return_value
     */
    private function __construct(private readonly Option $return_value)
    {
    }

    public static function wasmCallUnavailable(): self
    {
        return new self(Option::nothing(Result::class));
    }

    public static function successfulWasmCall(string $response): self
    {
        return new self(Option::fromValue(Result::ok($response)));
    }

    public static function failingWasmCall(): self
    {
        return new self(Option::fromValue(Result::err(Fault::fromMessage('Some error'))));
    }

    /**
     * @psalm-return Option<Result<string, Fault>>
     */
    public function call(string $wasm_path, string $input, string $read_only_dir_path, string $read_only_dir_guest_path): Option
    {
        $this->has_been_called = true;
        return $this->return_value;
    }

    public function hasBeenCalled(): bool
    {
        return $this->has_been_called;
    }
}
