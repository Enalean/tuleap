<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Http\Client;

use Exception;
use Http\Client\Common\Plugin;
use Http\Client\Exception\HttpException;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\Instrument\Prometheus\Prometheus;

final class HTTPOutboundResponseMetricCollector implements Plugin
{
    private const METRIC_NAME        = 'outbound_http_requests_total';
    private const METRIC_DESCRIPTION = 'Total number of outbound HTTP requests';

    private const VALID_HTTP_STATUS_CODE = [
        100 => true,
        101 => true,
        102 => true,
        200 => true,
        201 => true,
        202 => true,
        203 => true,
        204 => true,
        205 => true,
        206 => true,
        207 => true,
        208 => true,
        300 => true,
        301 => true,
        302 => true,
        303 => true,
        304 => true,
        305 => true,
        306 => true,
        307 => true,
        308 => true,
        400 => true,
        401 => true,
        402 => true,
        403 => true,
        404 => true,
        405 => true,
        406 => true,
        407 => true,
        408 => true,
        409 => true,
        410 => true,
        411 => true,
        412 => true,
        413 => true,
        414 => true,
        415 => true,
        416 => true,
        417 => true,
        418 => true,
        422 => true,
        423 => true,
        424 => true,
        425 => true,
        426 => true,
        428 => true,
        429 => true,
        431 => true,
        451 => true,
        500 => true,
        501 => true,
        502 => true,
        503 => true,
        504 => true,
        505 => true,
        506 => true,
        507 => true,
        508 => true,
        510 => true,
        511 => true,
    ];

    public function __construct(private readonly Prometheus $prometheus)
    {
    }

    #[\Override]
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(
            function (ResponseInterface $response): ResponseInterface {
                $this->processResponse($response);
                return $response;
            },
            function (Exception $exception): void {
                if ($exception instanceof HttpException) {
                    $this->processResponse($exception->getResponse());
                    throw $exception;
                }
                $this->prometheus->increment(self::METRIC_NAME, self::METRIC_DESCRIPTION, ['status' => 'failure', 'http_status_code' => 'invalid']);
                throw $exception;
            }
        );
    }

    private function processResponse(ResponseInterface $response): ResponseInterface
    {
        $http_status_code     = 'invalid';
        $response_status_code = $response->getStatusCode();
        if (isset(self::VALID_HTTP_STATUS_CODE[$response_status_code])) {
            $http_status_code = (string) $response_status_code;
        }

        $is_request_ssrf_filtered = FilteredOutboundRequestJustification::fromResponse($response)->isValue();

        $this->prometheus->increment(
            self::METRIC_NAME,
            self::METRIC_DESCRIPTION,
            [
                'status'           => ($is_request_ssrf_filtered ? 'ssrf_filtered' : 'fulfilled'),
                'http_status_code' => $http_status_code,
            ]
        );
        return $response;
    }
}
