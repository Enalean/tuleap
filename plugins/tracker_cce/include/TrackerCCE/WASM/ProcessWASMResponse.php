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

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ProcessWASMResponse implements WASMResponseProcessor
{
    public function __construct(
        public readonly LoggerInterface $logger,
        private readonly TreeMapper $mapper,
    ) {
    }

    public function processResponse(Ok | Err $wasm_response): Ok | Err
    {
        return $wasm_response->match(
        /** @return Ok<WASMResponseRepresentation>|Err<Fault> */
            function (string $json_response): Ok | Err {
                $this->logger->debug("Got response from WASM module : {$json_response}");
                try {
                    return Result::ok($this->mapper->map(
                        WASMResponseRepresentation::class,
                        Source::json($json_response)
                    ));
                } catch (MappingError | RuntimeException | InvalidSource $error) {
                    return Result::err(Fault::fromThrowableWithMessage(
                        $error,
                        'An invalid response has been received from the artifact post action WASM module: ' . $error->getMessage()
                    ));
                }
            },
            /** @return Err<Fault> */
            function (Fault $wasm_fault): Err {
                Fault::writeToLogger($wasm_fault, $this->logger, LogLevel::WARNING);
                return Result::err(Fault::fromMessage('An error occurred while running artifact post action WASM module'));
            }
        );
    }
}
