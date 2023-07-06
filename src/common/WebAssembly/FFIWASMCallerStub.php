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

abstract class FFIWASMCallerStub
{
    final private function __construct()
    {
    }

    /**
     * @psalm-param positive-int $max_exec_time_in_ms
     * @psalm-param positive-int $max_memory_size_in_bytes
     */
    abstract public function callWasmModule(string $filename, string $json, string $read_only_dir_path, string $read_only_dir_guest_path, int $max_exec_time_in_ms, int $max_memory_size_in_bytes): \FFI\CData;

    abstract public function freeCallWasmModuleOutput(\FFI\CData $json_ptr): void;
}
