<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\Tests\API;

use Psl\Json;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RequestWrapper;

final readonly class TestManagementAPIHelper
{
    public function __construct(
        private RequestWrapper $request_wrapper,
        private RequestFactoryInterface $request_factory,
    ) {
    }

    public function getTestDefinition(
        int $artifact_id,
        string $rest_user_name = BaseTestDataBuilder::TEST_USER_1_NAME,
    ): RESTTestDefinition {
        $response = $this->request_wrapper->getResponseByName(
            $rest_user_name,
            $this->request_factory->createRequest(
                'GET',
                'testmanagement_definitions/' . urlencode((string) $artifact_id)
            )
        );
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf('Could not get test definition #%s', $artifact_id));
        }
        return new RESTTestDefinition(Json\decode($response->getBody()->getContents()));
    }
}
