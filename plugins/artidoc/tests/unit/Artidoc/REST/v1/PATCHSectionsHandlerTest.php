<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Stubs\Domain\Document\Order\ReorderSectionsStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class PATCHSectionsHandlerTest extends TestCase
{
    public const SECTION_TO_MOVE_ID   = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const SECTION_REFERENCE_ID = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b6';

    private const PROJECT_ID = 101;

    private UUIDSectionIdentifierFactory $identifier_factory;
    private ArtidocWithContext $document;

    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());

        $this->document = new ArtidocWithContext(
            new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
        );
    }

    public function testHappyPath(): void
    {
        $reorder = ReorderSectionsStub::withSuccessfulReorder();

        $handler = new PATCHSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite($this->document),
            new SectionOrderBuilder($this->identifier_factory),
            $reorder,
        );

        $result = $handler->handle(
            1,
            OrderRepresentation::build(
                [self::SECTION_TO_MOVE_ID],
                'after',
                self::SECTION_REFERENCE_ID,
            ),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($reorder->isCalled());
    }

    public function testFaultWhenReorderFails(): void
    {
        $reorder = ReorderSectionsStub::withFailedReorder();

        $handler = new PATCHSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite($this->document),
            new SectionOrderBuilder($this->identifier_factory),
            $reorder,
        );

        $result = $handler->handle(
            1,
            OrderRepresentation::build(
                [self::SECTION_TO_MOVE_ID],
                'after',
                self::SECTION_REFERENCE_ID,
            ),
        );

        self::assertTrue(Result::isErr($result));
        self::assertTrue($reorder->isCalled());
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $reorder = ReorderSectionsStub::shouldNotBeCalled();

        $handler = new PATCHSectionsHandler(
            RetrieveArtidocWithContextStub::withoutDocument(),
            new SectionOrderBuilder($this->identifier_factory),
            $reorder,
        );

        $result = $handler->handle(
            1,
            OrderRepresentation::build(
                [self::SECTION_TO_MOVE_ID],
                'after',
                self::SECTION_REFERENCE_ID,
            ),
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($reorder->isCalled());
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $reorder = ReorderSectionsStub::shouldNotBeCalled();

        $handler = new PATCHSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead($this->document),
            new SectionOrderBuilder($this->identifier_factory),
            $reorder,
        );

        $result = $handler->handle(
            1,
            OrderRepresentation::build(
                [self::SECTION_TO_MOVE_ID],
                'after',
                self::SECTION_REFERENCE_ID,
            ),
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($reorder->isCalled());
    }
}
