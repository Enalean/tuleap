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

namespace Tuleap\TrackerCCE;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tracker;
use Tracker_Artifact_Changeset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationTask;
use Tuleap\Tracker\Webhook\ArtifactPayloadBuilder;
use Tuleap\TrackerCCE\WASM\WASMModuleCaller;
use Tuleap\TrackerCCE\WASM\WASMModulePathHelper;
use Tuleap\TrackerCCE\WASM\WASMResponseExecutor;
use Tuleap\TrackerCCE\WASM\WASMResponseRepresentation;
use Tuleap\User\CCEUser;
use function Psl\Json\encode as psl_json_encode;

final class CustomCodeExecutionTask implements PostCreationTask
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ArtifactPayloadBuilder $payload_builder,
        private readonly WASMModulePathHelper $module_path_helper,
        private readonly WASMModuleCaller $module_caller,
        private readonly WASMResponseExecutor $response_executor,
    ) {
    }

    public function execute(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        $this->logger->debug("CustomCodeExecutionTask called on artifact #{$changeset->getArtifact()->getId()} for changeset #{$changeset->getId()}");

        if ((int) $changeset->getSubmittedBy() === CCEUser::ID) {
            $this->logger->debug('Changeset submitted by forge__cce -> skip');
            return;
        }

        $payload = $this->payload_builder->buildPayload($changeset)->getPayload();
        $this->getWASMModulePath($changeset->getTracker())
            ->andThen(
            /** @psalm-return Ok<WASMResponseRepresentation>|Err<Fault> */
                function (string $wasm_module_path) use ($payload): Ok | Err {
                    $this->logger->debug("Found module to execute: {$wasm_module_path}");
                    return $this->module_caller->callWASMModule($wasm_module_path, psl_json_encode($payload));
                }
            )
            ->andThen(
            /** @psalm-return Ok<null>|Err<Fault> */
                function (WASMResponseRepresentation $response) use ($changeset): Ok | Err {
                    return $this->response_executor->executeResponse($response, $changeset->getArtifact());
                }
            )
            ->mapErr(fn(Fault $fault) => Fault::writeToLogger($fault, $this->logger, LogLevel::WARNING));

        $this->logger->debug('CustomCodeExecutionTask finished');
    }

    /**
     * @return Ok<string>|Err<Fault>
     */
    private function getWASMModulePath(Tracker $tracker): Ok | Err
    {
        $wasm_module_path = $this->module_path_helper->getPathForTracker($tracker);

        if (is_readable($wasm_module_path)) {
            return Result::ok($wasm_module_path);
        }

        return Result::err(Fault::fromMessage("WASM module for tracker #{$tracker->getId()} not found or not readable"));
    }
}
