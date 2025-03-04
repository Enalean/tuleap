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

use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SectionOrderBuilderTest extends TestCase
{
    private SectionIdentifier $section_1;
    private SectionIdentifier $section_2;
    private SectionIdentifier $section_3;
    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());

        $this->section_1 = $this->identifier_factory->buildIdentifier();
        $this->section_2 = $this->identifier_factory->buildIdentifier();
        $this->section_3 = $this->identifier_factory->buildIdentifier();
    }

    public function getBuilder(): SectionOrderBuilder
    {
        return new SectionOrderBuilder($this->identifier_factory);
    }

    public function testDirectionIsUnknown(): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString()],
            'unknown',
            $this->section_1->toString(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidDirectionFault::class, $result->error);
    }

    public function testComparedToIsNotValid(): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString()],
            'before',
            $this->section_1->toString() . 'invalid-uuid-chars',
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparedToFault::class, $result->error);
    }

    public function testIdsIsEmpty(): void
    {
        $result = $this->getBuilder()->build(
            [],
            'before',
            $this->section_1->toString(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidIdsFault::class, $result->error);
    }

    public function testIdsDoesNotContainAValidSectionIdentifier(): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString() . 'invalid-uuid-chars'],
            'before',
            $this->section_1->toString(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidIdsFault::class, $result->error);
    }

    public function testIdsContainsMoreThanOneSection(): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString(), $this->section_3->toString()],
            'before',
            $this->section_1->toString(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidIdsFault::class, $result->error);
    }

    public function testComparedToIsInIds(): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString()],
            'before',
            $this->section_2->toString(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotMoveSectionRelativelyToItselfFault::class, $result->error);
    }

    /**
     * @testWith ["after"]
     *           ["before"]
     */
    public function testHappyPath(string $direction): void
    {
        $result = $this->getBuilder()->build(
            [$this->section_2->toString()],
            $direction,
            $this->section_1->toString(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals($this->section_1, $result->value->compared_to);
        self::assertEquals($this->section_2, $result->value->identifier);
        self::assertEquals($direction, $result->value->direction->value);
    }
}
