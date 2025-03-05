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

namespace Tuleap\Artidoc\Domain\Document\Order;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Stubs\Domain\Document\Order\ReorderSectionsStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangeSectionOrderTest extends TestCase
{
    public const SECTION_TO_MOVE_ID   = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const SECTION_REFERENCE_ID = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b6';

    private const PROJECT_ID = 101;

    private ArtidocWithContext $document;
    private SectionOrderBuilder $order_builder;

    protected function setUp(): void
    {
        $this->order_builder = new SectionOrderBuilder(
            new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory())
        );

        $this->document = new ArtidocWithContext(
            new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
        );
    }

    public function testHappyPath(): void
    {
        $reorder = ReorderSectionsStub::withSuccessfulReorder();

        $handler = new ChangeSectionOrder(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite($this->document),
            $reorder,
        );

        $result = $this->order_builder->build([self::SECTION_TO_MOVE_ID], 'after', self::SECTION_REFERENCE_ID)
            ->andThen(static fn (SectionOrder $order) => $handler->reorder(1, $order));

        self::assertTrue(Result::isOk($result));
        self::assertTrue($reorder->isCalled());
    }

    public function testFaultWhenReorderFails(): void
    {
        $reorder = ReorderSectionsStub::withFailedReorder();

        $handler = new ChangeSectionOrder(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite($this->document),
            $reorder,
        );

        $result = $this->order_builder->build([self::SECTION_TO_MOVE_ID], 'after', self::SECTION_REFERENCE_ID)
            ->andThen(static fn (SectionOrder $order) => $handler->reorder(1, $order));

        self::assertTrue(Result::isErr($result));
        self::assertTrue($reorder->isCalled());
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $reorder = ReorderSectionsStub::shouldNotBeCalled();

        $handler = new ChangeSectionOrder(
            RetrieveArtidocWithContextStub::withoutDocument(),
            $reorder,
        );

        $result = $this->order_builder->build([self::SECTION_TO_MOVE_ID], 'after', self::SECTION_REFERENCE_ID)
            ->andThen(static fn (SectionOrder $order) => $handler->reorder(1, $order));

        self::assertTrue(Result::isErr($result));
        self::assertFalse($reorder->isCalled());
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $reorder = ReorderSectionsStub::shouldNotBeCalled();

        $handler = new ChangeSectionOrder(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead($this->document),
            $reorder,
        );

        $result = $this->order_builder->build([self::SECTION_TO_MOVE_ID], 'after', self::SECTION_REFERENCE_ID)
            ->andThen(static fn (SectionOrder $order) => $handler->reorder(1, $order));

        self::assertTrue(Result::isErr($result));
        self::assertFalse($reorder->isCalled());
    }
}
