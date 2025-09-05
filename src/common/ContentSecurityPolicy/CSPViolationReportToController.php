<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ContentSecurityPolicy;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class CSPViolationReportToController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EmitterInterface $emitter,
        ResponseFactoryInterface $response_factory,
        LoggerInterface $logger,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory = $response_factory;
        $this->logger           = $logger;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $csp_raw_report = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->response_factory->createResponse(400);
        }

        if (! isset($csp_raw_report['csp-report'])) {
            return $this->response_factory->createResponse(400);
        }

        $csp_report = $csp_raw_report['csp-report'] + ['user-agent' => $request->getHeaderLine('User-Agent')];

        $this->logger->info(
            json_encode($csp_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        return $this->response_factory->createResponse(204);
    }
}
