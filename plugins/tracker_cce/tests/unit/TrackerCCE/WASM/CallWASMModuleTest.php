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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\WebAssembly\WASMCaller;
use Tuleap\WebAssembly\WASMCallerStub;

final class CallWASMModuleTest extends TestCase
{
    private WASMResponseProcessor&MockObject $response_processor;

    protected function setUp(): void
    {
        $this->response_processor = $this->createMock(WASMResponseProcessor::class);
    }

    public function testItReturnsErrIfCallerReturnsNothing(): void
    {
        $caller = $this->buildWASMModuleCaller(WASMCallerStub::wasmCallUnavailable());
        $result = $caller->callWASMModule('path', 'payload');
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsResponseProcessorWhenCallerReturnsAValue(): void
    {
        $caller = $this->buildWASMModuleCaller(WASMCallerStub::successfulWasmCall('wasm-response'));
        $this->response_processor->expects(self::once())->method('processResponse')->willReturn(Result::ok(new WASMResponseRepresentation([], null)));
        $result = $caller->callWASMModule('path', 'payload');
        self::assertTrue(Result::isOk($result));
        self::assertInstanceOf(WASMResponseRepresentation::class, $result->value);
    }

    private function buildWASMModuleCaller(WASMCaller $wasm_caller): CallWASMModule
    {
        return new CallWASMModule($wasm_caller, $this->response_processor, new NullLogger());
    }
}
