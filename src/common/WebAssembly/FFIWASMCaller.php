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

namespace Tuleap\WebAssembly;

final class FFIWASMCaller implements WASMCaller
{
    /**
     * @var \FFI&FFIWASMCallerStub $ffi
     */
    private \FFI $ffi;
    private const MAX_EXEC_TIME_IN_MS      = 80;
    private const MAX_MEMORY_SIZE_IN_BYTES = 3145728; /* 3 Mo */
    private const HEADER_PATH              = __DIR__ . '/../../additional-packages/wasmtime-wrapper-lib/wasmtimewrapper.h';

    public function __construct()
    {
        /**
         * @var ?(\FFI&FFIWASMCallerStub) $ffi_tmp
         */
        $ffi_tmp = \FFI::load(self::HEADER_PATH);
        if ($ffi_tmp === null) {
            throw new \LogicException("Could not load C declaration from " . self::HEADER_PATH);
        }
        $this->ffi = $ffi_tmp;
    }

    public function call(string $wasm_path, string $json_input): string
    {
        $json_output     = $this->ffi->callWasmModule($wasm_path, $json_input, self::MAX_EXEC_TIME_IN_MS, self::MAX_MEMORY_SIZE_IN_BYTES);
        $json_output_php = \FFI::string($json_output);
        $this->ffi->freeCallWasmModuleOutput($json_output);

        return $json_output_php;
    }
}
