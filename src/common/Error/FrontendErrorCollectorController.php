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

namespace Tuleap\Error;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\ProvideCurrentUser;

final class FrontendErrorCollectorController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const METRIC_NAME = 'collected_frontend_errors_total';

    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private LoggerInterface $logger,
        private Prometheus $prometheus,
        private ProvideCurrentUser $current_user_provider,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $collected_error_raw_report = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->response_factory->createResponse(400);
        }

        if (! isset($collected_error_raw_report['error'])) {
            return $this->response_factory->createResponse(400);
        }

        $report = $collected_error_raw_report + [
            'user-agent' => $request->getHeaderLine('User-Agent'),
            'user-id' => (int) $this->current_user_provider->getCurrentUser()->getId(),
        ];

        $this->logger->error(
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
        $this->prometheus->increment(self::METRIC_NAME, 'Total number of collected frontend errors');

        return $this->response_factory->createResponse(204);
    }
}
