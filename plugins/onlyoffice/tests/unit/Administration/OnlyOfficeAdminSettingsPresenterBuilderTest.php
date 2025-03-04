<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class OnlyOfficeAdminSettingsPresenterBuilderTest extends TestCase
{
    public function testGetPresenter(): void
    {
        $retriever = IRetrieveDocumentServersStub::buildWith(
            DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com/1', new ConcealedString('')),
            DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com/2', new ConcealedString('123456')),
        );

        $presenter = (new OnlyOfficeAdminSettingsPresenterBuilder($retriever))
            ->getPresenter(CSRFSynchronizerTokenStub::buildSelf());

        self::assertCount(2, $presenter->servers);
        self::assertEquals('https://example.com/1', $presenter->servers[0]->server_url);
        self::assertFalse($presenter->servers[0]->has_existing_secret);
        self::assertEquals('https://example.com/2', $presenter->servers[1]->server_url);
        self::assertTrue($presenter->servers[1]->has_existing_secret);
    }
}
