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

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class FilteredOutboundRequestJustificationTest extends TestCase
{
    public function testDoesNotBuildAJustificationWhenRequestDoesNotAppearToHaveBeenFiltered(): void
    {
        $justification = FilteredOutboundRequestJustification::fromResponse(HTTPFactoryBuilder::responseFactory()->createResponse());

        self::assertTrue($justification->isNothing());
    }

    public function testExtractsInformationForFilteredRequests(): void
    {
        $reason                         = 'Something bad';
        $response_from_filtered_request = HTTPFactoryBuilder::responseFactory()->createResponse(407)
            ->withHeader('X-Smokescreen-Error', $reason);

        $justification = FilteredOutboundRequestJustification::fromResponse($response_from_filtered_request);

        self::assertTrue($justification->isValue());
        self::assertSame($reason, $justification->unwrapOr(null)?->reason);
    }

    public function testRejectedRequestsWithoutA407StatusCodeAreNotSSRFFiltered(): void
    {
        $response_from_filtered_request = HTTPFactoryBuilder::responseFactory()->createResponse(502)
            ->withHeader('X-Smokescreen-Error', 'Failed to connect to remote host: ...');

        $justification = FilteredOutboundRequestJustification::fromResponse($response_from_filtered_request);

        self::assertTrue($justification->isNothing());
    }
}
