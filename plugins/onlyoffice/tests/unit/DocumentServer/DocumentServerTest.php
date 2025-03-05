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

namespace Tuleap\OnlyOffice\DocumentServer;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentServerTest extends TestCase
{
    public function testDetectsWhenASecretKeyIsAvailable(): void
    {
        self::assertTrue(DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString('something_secret'))->has_existing_secret);
        self::assertFalse(DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString(''))->has_existing_secret);
    }

    public function testAllowsProjectWhenNoProjectRestrictionsAreSet(): void
    {
        $document_server = DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://example.com', new ConcealedString('something_secret'));

        self::assertTrue($document_server->isProjectAllowed(ProjectTestBuilder::aProject()->build()));
    }

    public function testOnlyAllowsSpecificProjectWhenProjectRestrictionsAreSet(): void
    {
        $allowed_project     = ProjectTestBuilder::aProject()->withId(102)->build();
        $not_allowed_project = ProjectTestBuilder::aProject()->withId(403)->build();

        $document_server = DocumentServer::withProjectRestrictions(
            new UUIDTestContext(),
            'https://example.com',
            new ConcealedString('something_secret'),
            [
                (int) $allowed_project->getID() => new RestrictedProject((int) $allowed_project->getID(), $allowed_project->getUnixNameMixedCase(), $allowed_project->getPublicName()),
            ],
        );

        self::assertTrue($document_server->isProjectAllowed($allowed_project));
        self::assertFalse($document_server->isProjectAllowed($not_allowed_project));
    }
}
