<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElementFactory;
use Tracker_Semantic_Title;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Stubs\Document\Field\RetrieveConfiguredFieldStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ConfiguredFieldCollectionBuilderTest extends TestCase
{
    private SectionIdentifier $section_id;
    private ArtidocWithContext $artidoc;
    private PFUser $user;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker    = TrackerTestBuilder::aTracker()->withId(1001)->build();
        $this->section_id = (new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $this->artidoc    = new ArtidocWithContext(new ArtidocDocument(['item_id' => 123]));
        $this->user       = UserTestBuilder::buildWithDefaults();
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
    }

    public function testEmptyConfiguredFields(): void
    {
        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withoutConfiguredFields(),
            $this->createMock(Tracker_FormElementFactory::class),
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty($builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker));
    }

    public function testExcludeFieldsThatAreNotString(): void
    {
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getFieldById')
            ->with(123)
            ->willReturn(DateFieldBuilder::aDateField(123)->build());

        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields([
                ['field_id' => 123, 'display_type' => DisplayType::COLUMN],
            ]),
            $factory,
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty($builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker));
    }

    public function testExcludeFieldsThatAreUnused(): void
    {
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getFieldById')
            ->with(123)
            ->willReturn(
                StringFieldBuilder::aStringField(123)
                    ->unused()
                    ->build()
            );

        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields([
                ['field_id' => 123, 'display_type' => DisplayType::COLUMN],
            ]),
            $factory,
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty($builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker));
    }

    public function testExcludeFieldsThatAreNotReadable(): void
    {
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getFieldById')
            ->with(123)
            ->willReturn(
                StringFieldBuilder::aStringField(123)
                    ->withReadPermission($this->user, false)
                    ->build()
            );

        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields([
                ['field_id' => 123, 'display_type' => DisplayType::COLUMN],
            ]),
            $factory,
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty($builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker));
    }

    public function testExcludeFieldThatIsSemanticTitle(): void
    {
        $field_string = StringFieldBuilder::aStringField(123)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();

        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getFieldById')
            ->with(123)
            ->willReturn(
                $field_string
            );

        \Tracker_Semantic_Title::setInstance(
            new Tracker_Semantic_Title($this->tracker, $field_string),
            $this->tracker,
        );

        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields([
                ['field_id' => 123, 'display_type' => DisplayType::COLUMN],
            ]),
            $factory,
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty($builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker));
    }

    public function testHappyPath(): void
    {
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getFieldById')
            ->willReturnCallback(fn (int $id) => match ($id) {
                123 => StringFieldBuilder::aStringField(123)
                    ->inTracker($this->tracker)
                    ->withReadPermission($this->user, true)
                    ->build(),
                124 => StringFieldBuilder::aStringField(124)
                    ->inTracker($this->tracker)
                    ->withReadPermission($this->user, true)
                    ->build(),
            });

        \Tracker_Semantic_Title::setInstance(
            new Tracker_Semantic_Title($this->tracker, null),
            $this->tracker,
        );

        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields([
                ['field_id' => 123, 'display_type' => DisplayType::COLUMN],
                ['field_id' => 124, 'display_type' => DisplayType::BLOCK],
            ]),
            $factory,
        );

        $scenarios = [
            $builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker),
            $builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker),
        ];

        foreach ($scenarios as $configured_fields) {
            self::assertCount(2, $configured_fields);
            self::assertSame(123, $configured_fields[0]->field->getId());
            self::assertSame('column', $configured_fields[0]->display_type->value);
            self::assertSame(124, $configured_fields[1]->field->getId());
            self::assertSame('block', $configured_fields[1]->display_type->value);
        }

        $another_tracker = TrackerTestBuilder::aTracker()->withId(1002)->build();
        self::assertEmpty(
            $builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($another_tracker),
        );
        self::assertEmpty(
            $builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($another_tracker),
        );
    }
}
