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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\RestrictedProject;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeAvailabilityCheckerTest extends TestCase
{
    private const PROJECT_ID = 101;

    public function testItLogsThatServerUrlIsNotConfigured(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $checker = new OnlyOfficeAvailabilityChecker(
            $logger,
            IRetrieveDocumentServersStub::buildWithoutServer(),
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with('No document server with existing secret key has been defined');

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItLogsThatServerSecretIsNotConfigured(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $checker = new OnlyOfficeAvailabilityChecker(
            $logger,
            IRetrieveDocumentServersStub::buildWith(DocumentServer::withoutProjectRestrictions(1, 'https://example.com', new ConcealedString(''))),
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with('No document server with existing secret key has been defined');

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItReturnFalseIfProjectDoesNotUseDocman(): void
    {
        $checker = new OnlyOfficeAvailabilityChecker(
            new NullLogger(),
            IRetrieveDocumentServersStub::buildWith(DocumentServer::withoutProjectRestrictions(1, 'https://example.com', new ConcealedString('very_secret'))),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('example')
            ->build();

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testHappyPathWithoutProjectRestrictions(): void
    {
        $document_server = DocumentServer::withoutProjectRestrictions(1, 'https://example.com', new ConcealedString('very_secret'));
        $checker         = new OnlyOfficeAvailabilityChecker(
            new NullLogger(),
            IRetrieveDocumentServersStub::buildWith($document_server),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('example')
            ->withUsedService(\DocmanPlugin::SERVICE_SHORTNAME)
            ->build();

        self::assertTrue($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testHappyPathWithProjectRestrictions(): void
    {
        $document_server = DocumentServer::withProjectRestrictions(
            1,
            'https://example.com',
            new ConcealedString('very_secret'),
            [
                self::PROJECT_ID => new RestrictedProject(self::PROJECT_ID, 'blah', 'Blah'),
            ],
        );
        $checker         = new OnlyOfficeAvailabilityChecker(
            new NullLogger(),
            IRetrieveDocumentServersStub::buildWith($document_server),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('example')
            ->withUsedService(\DocmanPlugin::SERVICE_SHORTNAME)
            ->build();

        self::assertTrue($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }
}
