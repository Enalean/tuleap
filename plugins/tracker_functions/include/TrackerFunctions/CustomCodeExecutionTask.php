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

namespace Tuleap\TrackerFunctions;

use Plugin;
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
use Tuleap\TrackerFunctions\Administration\CheckFunctionIsActivated;
use Tuleap\TrackerFunctions\Logs\FunctionLogLine;
use Tuleap\TrackerFunctions\Logs\SaveFunctionLog;
use Tuleap\TrackerFunctions\Notification\TrackerAdministratorNotificationSender;
use Tuleap\TrackerFunctions\WASM\WASMFunctionCaller;
use Tuleap\TrackerFunctions\WASM\WASMFunctionPathHelper;
use Tuleap\TrackerFunctions\WASM\WASMResponseExecutor;
use Tuleap\TrackerFunctions\WASM\WASMResponseRepresentation;
use function Psl\Json\encode as psl_json_encode;

final class CustomCodeExecutionTask implements PostCreationTask
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ArtifactPayloadBuilder $payload_builder,
        private readonly WASMFunctionPathHelper $function_path_helper,
        private readonly WASMFunctionCaller $function_caller,
        private readonly WASMResponseExecutor $response_executor,
        private readonly SaveFunctionLog $log_dao,
        private readonly CheckFunctionIsActivated $check_function_is_activated,
        private readonly TrackerAdministratorNotificationSender $administrator_notification_sender,
        private readonly Plugin $tracker_functions_plugin,
    ) {
    }

    public function execute(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        $this->logger->debug("CustomCodeExecutionTask called on artifact #{$changeset->getArtifact()->getId()} for changeset #{$changeset->getId()}");

        if ($changeset->getSubmitter()->isATechnicalUser()) {
            $this->logger->debug("Changeset submitted by technical user ({$changeset->getSubmitter()->getUserName()}) -> skip");
            return;
        }

        $project = $changeset->getTracker()->getProject();
        if (! $this->tracker_functions_plugin->isAllowed((int) $project->getID())) {
            $this->logger->debug("tracker functions plugins not allowed for project #{$project->getID()} -> skip");
            return;
        }

        if (! $this->check_function_is_activated->isFunctionActivated($changeset->getTracker()->getId())) {
            $this->logger->debug('Function is deactivated -> skip');
            return;
        }

        $source_payload    = psl_json_encode($this->payload_builder->buildPayload($changeset)->getPayload());
        $generated_payload = '';
        $this->getWASMFunctionPath($changeset->getTracker())
            ->andThen(
            /** @psalm-return Ok<WASMResponseRepresentation>|Err<Fault> */
                function (string $wasm_function_path) use ($source_payload): Ok|Err {
                    $this->logger->debug("Found function to execute: {$wasm_function_path}");
                    return $this->function_caller->callWASMFunction($wasm_function_path, $source_payload);
                }
            )
            ->andThen(
            /** @psalm-return Ok<null>|Err<Fault> */
                function (WASMResponseRepresentation $response) use ($changeset, &$generated_payload): Ok|Err {
                    $generated_payload = $response;
                    return $this->response_executor->executeResponse($response, $changeset->getArtifact());
                }
            )
            ->match(
                fn() => $this->log_dao->saveFunctionLogLine(FunctionLogLine::buildPassed(
                    (int) $changeset->getId(),
                    $source_payload,
                    psl_json_encode($generated_payload),
                    (new \DateTimeImmutable())->getTimestamp(),
                )),
                function (Fault $fault) use ($changeset, $source_payload) {
                    Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                    $this->log_dao->saveFunctionLogLine(FunctionLogLine::buildError(
                        (int) $changeset->getId(),
                        $source_payload,
                        (string) $fault,
                        (new \DateTimeImmutable())->getTimestamp(),
                    ));
                    $this->administrator_notification_sender->sendNotificationToTrackerAdministrator($changeset);
                }
            );

        $this->logger->debug('CustomCodeExecutionTask finished');
    }

    /**
     * @return Ok<string>|Err<Fault>
     */
    private function getWASMFunctionPath(Tracker $tracker): Ok|Err
    {
        $wasm_function_path = $this->function_path_helper->getPathForTracker($tracker);

        if (is_readable($wasm_function_path)) {
            return Result::ok($wasm_function_path);
        }

        return Result::err(Fault::fromMessage("Tuleap function for tracker #{$tracker->getId()} not found or not readable"));
    }
}
