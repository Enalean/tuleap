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

namespace Tuleap\TrackerCCE\WASM;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\WebAssembly\WASMCaller;
use Tuleap\WebAssembly\WASMExecutionException;

final class CallWASMModule implements WASMModuleCaller
{
    public function __construct(
        private readonly WASMCaller $wasm_caller,
        private readonly WASMResponseProcessor $response_processor,
    ) {
    }

    public function callWASMModule(string $wasm_module_path, string $payload): Ok | Err
    {
        try {
            return $this->wasm_caller
                ->call($wasm_module_path, $payload, [])
                ->mapOr(
                    $this->response_processor->processResponse(...),
                    Result::err(Fault::fromMessage('WASM module returns nothing'))
                );
        } catch (WASMExecutionException $exception) {
            return Result::err(Fault::fromThrowable($exception));
        }
    }
}
