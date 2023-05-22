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

use Psr\Http\Message\ResponseInterface;
use Tuleap\Option\Option;

/**
 * @psalm-readonly
 */
final class FilteredOutboundRequestJustification
{
    private const SMOKESCREEN_ERROR_HEADER = 'X-Smokescreen-Error';

    private function __construct(public readonly string $reason)
    {
    }

    /**
     * @psalm-return Option<self>
     */
    public static function fromResponse(ResponseInterface $response): Option
    {
        if ($response->getStatusCode() !== 407) {
            return Option::nothing(self::class);
        }
        $filtered_request_header = $response->getHeaderLine(self::SMOKESCREEN_ERROR_HEADER);
        if ($filtered_request_header === '') {
            return Option::nothing(self::class);
        }

        return Option::fromValue(
            new self(
                $filtered_request_header,
            )
        );
    }
}
