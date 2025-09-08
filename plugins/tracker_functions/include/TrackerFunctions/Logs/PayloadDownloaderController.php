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

namespace Tuleap\TrackerFunctions\Logs;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\User\ProvideCurrentUser;
use ZipStream\CompressionMethod;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

final class PayloadDownloaderController extends DispatchablePSR15Compatible
{
    public function __construct(
        EmitterInterface $emitter,
        private readonly RetrievePayloadsForChangeset $payloads_retriever,
        private readonly ProvideCurrentUser $current_user_provider,
        private readonly RetrieveTracker $tracker_retriever,
        private readonly BinaryFileResponseBuilder $binary_file_response_builder,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $changeset_id = (int) $request->getAttributes()['changeset_id'];

        $response = $this->payloads_retriever->searchPayloadsByChangesetID($changeset_id)->mapOr(
            function (FunctionLogPayloads $log_payloads) use ($changeset_id, $request): ResponseInterface {
                $tracker = $this->tracker_retriever->getTrackerById($log_payloads->tracker_id);
                if ($tracker === null || ! $tracker->userIsAdmin($this->current_user_provider->getCurrentUser())) {
                    throw new NotFoundException();
                }

                return $this->binary_file_response_builder->fromCallback(
                    $request,
                    function () use ($log_payloads, $changeset_id): void {
                        $zip_stream = new ZipStream(
                            OperationMode::NORMAL,
                            sprintf('Changeset ID %d', $changeset_id),
                            null,
                            CompressionMethod::STORE,
                            6,
                            true,
                            true,
                            false,
                        );

                        $zip_stream->addFile('source_payload.json', $log_payloads->source_payload);
                        $log_payloads->generated_payload->apply(
                            fn(string $generated_payload) => $zip_stream->addFile('generated_payload.json', $generated_payload)
                        );

                        $zip_stream->finish();
                    },
                    sprintf('tuleap_function_changeset_%d_payloads.zip', $changeset_id),
                    'application/zip'
                );
            },
            null
        );

        if ($response === null) {
            throw new NotFoundException();
        }
        return $response;
    }

    public static function buildURL(int $changeset_id): string
    {
        return '/tracker_functions/download_payloads/' . urlencode((string) $changeset_id);
    }
}
