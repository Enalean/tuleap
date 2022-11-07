<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

final class PreReceiveAnalyzeFFI implements WASMCaller
{
    /**
     * @var \FFI&WASMFFICallerStub $ffi
     */
    private \FFI $ffi;
    private const HEADER_PATH = __DIR__ . '/../../../additional-packages/wasmtime-wrapper-lib/wasmtimewrapper.h';
    private const MODULE_PATH = __DIR__ . '/../../../additional-packages/pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm';

    public function __construct()
    {
        /**
         * @var ?(\FFI&WASMFFICallerStub) $ffi_tmp
         */
        $ffi_tmp = \FFI::load(self::HEADER_PATH);
        if ($ffi_tmp === null) {
            throw new \LogicException("Could not load C declaration from " . self::HEADER_PATH);
        }
        $this->ffi = $ffi_tmp;
    }

    public function call(string $json_input): string
    {
        $json_output     = $this->ffi->callWasmModule(self::MODULE_PATH, $json_input);
        $json_output_php = \FFI::string($json_output);
        $this->ffi->freeCallWasmModuleOutput($json_output);

        return $json_output_php;
    }
}
