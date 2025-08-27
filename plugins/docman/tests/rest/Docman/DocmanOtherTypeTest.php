<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;
use Tuleap\REST\BaseTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DocmanOtherTypeTest extends DocmanTestExecutionHelper
{
    public function testOptionsMetadata(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_other_type_documents/123/metadata'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        self::assertSame(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertSame($response->getStatusCode(), 200);
    }

    public function testOptionsPermissions(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_other_type_documents/123/permissions'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        self::assertSame(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertSame($response->getStatusCode(), 200);
    }
}
