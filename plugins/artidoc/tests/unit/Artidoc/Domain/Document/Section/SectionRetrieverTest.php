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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Document\SearchOneSectionStub;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\CollectRequiredSectionInformationStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SectionRetrieverTest extends TestCase
{
    public const SECTION_ID  = '018f77dc-eebb-73b3-9dfd-a294e5cfa1b5';
    public const ITEM_ID     = 123;
    public const ARTIFACT_ID = 1001;
    private SectionIdentifierFactory $identifier_factory;

    protected function setUp(): void
    {
        $this->identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testHappyPathUserCanRead(): void
    {
        $collector = CollectRequiredSectionInformationStub::withRequiredInformationFor(self::ARTIFACT_ID);

        $builder = new SectionRetriever(
            SearchOneSectionStub::withResults($this->getMatchingRetrievedSection()),
            RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                )
            ),
            $collector,
        );

        $result = $builder->retrieveSectionUserCanRead($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isOk($result));
        self::assertSame(
            self::ARTIFACT_ID,
            $result->value->content->apply(
                static fn ($id) => Result::ok($id),
                static fn () => Result::ok(null),
            )->unwrapOr(null),
        );
        self::assertTrue($collector->isCalled());

        $result = $builder->retrieveSectionUserCanWrite($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
    }

    public function testHappyPathUserCanWrite(): void
    {
        $collector = CollectRequiredSectionInformationStub::withRequiredInformationFor(self::ARTIFACT_ID);

        $builder = new SectionRetriever(
            SearchOneSectionStub::withResults($this->getMatchingRetrievedSection()),
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                )
            ),
            $collector,
        );

        $result = $builder->retrieveSectionUserCanWrite($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isOk($result));
        self::assertSame(
            self::ARTIFACT_ID,
            $result->value->content->apply(
                static fn ($id) => Result::ok($id),
                static fn () => Result::ok(null),
            )->unwrapOr(null),
        );
        self::assertTrue($collector->isCalled());
    }

    public function testWhenSectionIsNotFound(): void
    {
        $collector = CollectRequiredSectionInformationStub::withRequiredInformationFor(self::ARTIFACT_ID);

        $builder = new SectionRetriever(
            SearchOneSectionStub::withoutResults(),
            RetrieveArtidocWithContextStub::shouldNotBeCalled(),
            $collector,
        );

        $result = $builder->retrieveSectionUserCanRead($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertFalse($collector->isCalled());

        $result = $builder->retrieveSectionUserCanWrite($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertFalse($collector->isCalled());
    }

    public function testWhenDocumentIsNotFound(): void
    {
        $collector = CollectRequiredSectionInformationStub::withRequiredInformationFor(self::ARTIFACT_ID);

        $builder = new SectionRetriever(
            SearchOneSectionStub::withResults($this->getMatchingRetrievedSection()),
            RetrieveArtidocWithContextStub::withoutDocument(),
            $collector,
        );

        $result = $builder->retrieveSectionUserCanRead($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertFalse($collector->isCalled());

        $result = $builder->retrieveSectionUserCanWrite($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertFalse($collector->isCalled());
    }

    public function testFaultWhenArtifactDoesNotHaveRequiredInformation(): void
    {
        $collector = CollectRequiredSectionInformationStub::withoutRequiredInformation(self::ARTIFACT_ID);

        $builder = new SectionRetriever(
            SearchOneSectionStub::withResults($this->getMatchingRetrievedSection()),
            RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => self::ITEM_ID]),
                )
            ),
            $collector,
        );

        $result = $builder->retrieveSectionUserCanRead($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertTrue($collector->isCalled());

        $result = $builder->retrieveSectionUserCanWrite($this->identifier_factory->buildFromHexadecimalString(self::SECTION_ID));
        self::assertTrue(Result::isErr($result));
        self::assertTrue($collector->isCalled());
    }

    private function getMatchingRetrievedSection(): RetrievedSection
    {
        return RetrievedSection::fromArtifact([
            'id' => SectionIdentifierStub::create(),
            'item_id' => self::ITEM_ID,
            'artifact_id' => self::ARTIFACT_ID,
            'rank' => 0,
            'level' => 1,
        ]);
    }
}
