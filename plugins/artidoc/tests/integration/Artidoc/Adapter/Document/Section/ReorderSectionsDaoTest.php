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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReorderSectionsDaoTest extends TestIntegrationTestCase
{
    private ArtidocWithContext $artidoc_101;
    private ArtidocWithContext $artidoc_102;

    protected function setUp(): void
    {
        $this->artidoc_101 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $this->artidoc_102 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 102]));
    }

    public function testReorderSections(): void
    {
        $save_dao = new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());

        $uuid_1 = $save_dao
            ->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001, Level::One),
            )->map(fn (SectionIdentifier $identifier) => $identifier->toString())
            ->unwrapOr('');
        self::assertNotSame('', $uuid_1);
        $uuid_2 = $save_dao
            ->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002, Level::One),
            )->map(fn (SectionIdentifier $identifier) => $identifier->toString())
            ->unwrapOr('');
        self::assertNotSame('', $uuid_2);
        $uuid_3 = $save_dao
            ->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003, Level::One),
            )->map(fn (SectionIdentifier $identifier) => $identifier->toString())
            ->unwrapOr('');
        self::assertNotSame('', $uuid_3);
        $save_dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1001, Level::One),
        );
        $save_dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1002, Level::One),
        );
        $save_dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003, Level::One),
        );

        $reorder_dao = new ReorderSectionsDao();

        $order_builder = new SectionOrderBuilder($this->getSectionIdentifierFactory());

        // "before", at the beginning
        $order = $order_builder->build([$uuid_2], 'before', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1002, 1001, 1003]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1002, 1003]);

        // "before", in the middle
        $order = $order_builder->build([$uuid_3], 'before', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1002, 1003, 1001]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1002, 1003]);

        // "after", at the end
        $order = $order_builder->build([$uuid_2], 'after', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1003, 1001, 1002]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1002, 1003]);

        // "after", in the middle
        $order = $order_builder->build([$uuid_3], 'after', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001, 1003, 1002]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1001, 1002, 1003]);
    }

    public function testExceptionWhenReorderSectionsOutsideOfDocument(): void
    {
        $save_dao = new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());

        $uuid_1 = $save_dao
            ->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001, Level::One),
            )->map(fn (SectionIdentifier $identifier) => $identifier->toString())
            ->unwrapOr('');
        self::assertNotSame('', $uuid_1);
        $uuid_2 = $save_dao
            ->saveSectionAtTheEnd(
                $this->artidoc_102,
                ContentToInsert::fromArtifactId(1002, Level::One),
            )->map(fn (SectionIdentifier $identifier) => $identifier->toString())
            ->unwrapOr('');
        self::assertNotSame('', $uuid_2);

        $reorder_dao = new ReorderSectionsDao();

        $order_builder = new SectionOrderBuilder($this->getSectionIdentifierFactory());

        $order = $order_builder->build([$uuid_2], 'before', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnknownSectionToMoveFault::class, $result->error);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1002]);

        $order = $order_builder->build([$uuid_2], 'before', $uuid_1);
        self::assertTrue(Result::isOk($order));
        $result = $reorder_dao->reorder(
            $this->artidoc_102,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToReorderSectionOutsideOfDocumentFault::class, $result->error);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1002]);
    }

    /**
     * @return UUIDSectionIdentifierFactory
     */
    private function getSectionIdentifierFactory(): SectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }

    /**
     * @return UUIDFreetextIdentifierFactory
     */
    private function getFreetextIdentifierFactory(): FreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }
}
