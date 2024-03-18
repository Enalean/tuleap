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

namespace Tuleap\TrackerFunctions\WASM;

use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\PUTHandler;
use Tuleap\User\TuleapFunctionsUser;

final class ExecuteWASMResponse implements WASMResponseExecutor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PUTHandler $handler,
    ) {
    }

    public function executeResponse(WASMResponseRepresentation $response, Artifact $artifact): Ok|Err
    {
        try {
            $this->handler->handle($response->values, $artifact, new TuleapFunctionsUser(), $response->comment);
            $this->logger->debug("Artifact update successful");
        } catch (RestException $exception) {
            return Result::err(Fault::fromThrowable($exception));
        }

        return Result::ok(null);
    }
}
