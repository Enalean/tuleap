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

use Http\Client\Common\Plugin;
use Http\Client\Exception\HttpException;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;

#[ConfigKeyCategory('Outbound HTTP requests')]
final class FilteredOutboundHTTPResponseAlerter implements Plugin
{
    #[ConfigKey('Alert when an outbound HTTP requests is filtered (either ' . self::ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_NEVER_VALUE  . ' or ' . self::ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_SYSTEM_CHECK_VALUE . ')')]
    #[ConfigKeyString(self::ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_SYSTEM_CHECK_VALUE)]
    public const ALERT_FILTERED_OUTBOUND_HTTP_REQUEST                    = 'http_outbound_requests_filtered_alert';
    public const ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_NEVER_VALUE        = 'never';
    public const ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_SYSTEM_CHECK_VALUE = 'system-check';


    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FilteredOutboundHTTPResponseAlerterDAO $alerter_dao,
    ) {
    }

    #[\Override]
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        if (! OutboundHTTPRequestProxy::isFilteringProxyEnabled()) {
            return $next($request);
        }

        return $next($request)->then(
            function (ResponseInterface $response) use ($request): ResponseInterface {
                $this->processResponse($request, $response);
                return $response;
            },
            function (\Exception $exception) use ($request): void {
                if ($exception instanceof HttpException) {
                    $this->processResponse($request, $exception->getResponse());
                }
                throw $exception;
            }
        );
    }

    private function processResponse(RequestInterface $request, ResponseInterface $response): void
    {
        FilteredOutboundRequestJustification::fromResponse($response)->apply(
            function (FilteredOutboundRequestJustification $filtering_justification) use ($request): void {
                $this->logger->error(
                    sprintf(
                        'A possible SSRF attempt was blocked: %s (%s)',
                        (string) $request->getUri(),
                        $filtering_justification->reason,
                    ),
                );
                if (\ForgeConfig::get(self::ALERT_FILTERED_OUTBOUND_HTTP_REQUEST) !== self::ALERT_FILTERED_OUTBOUND_HTTP_REQUEST_NEVER_VALUE) {
                    $this->alerter_dao->markNewFilteredRequest();
                }
            }
        );
    }
}
