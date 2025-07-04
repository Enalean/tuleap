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
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Stubs\Document\Field\RetrieveConfiguredFieldStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

#[DisableReturnValueGenerationForTestDoubles]
final class ConfiguredFieldCollectionBuilderTest extends TestCase
{
    private SectionIdentifier $section_id;
    private ArtidocWithContext $artidoc;
    private PFUser $user;
    private \Tuleap\Tracker\Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker    = TrackerTestBuilder::aTracker()->withId(1001)->build();
        $this->section_id = (new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $this->artidoc    = new ArtidocWithContext(new ArtidocDocument(['item_id' => 123]));
        $this->user       = UserTestBuilder::buildWithDefaults();
    }

    public function testEmptyConfiguredFields(): void
    {
        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withoutConfiguredFields(),
            new SuitableFieldRetriever(
                RetrieveUsedFieldsStub::withNoFields(),
                RetrieveSemanticDescriptionFieldStub::withNoField(),
                RetrieveSemanticTitleFieldStub::build(),
            ),
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty(
            $builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker)
        );
    }

    public function testExcludeFieldsThatAreNotSuitable(): void
    {
        $builder = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields(new ArtifactSectionField(123, DisplayType::COLUMN)),
            new SuitableFieldRetriever(
                RetrieveUsedFieldsStub::withFields(
                    ExternalFieldBuilder::anExternalField(123)
                        ->withReadPermission($this->user, true)
                        ->inTracker($this->tracker)
                        ->build()
                ),
                RetrieveSemanticDescriptionFieldStub::withNoField(),
                RetrieveSemanticTitleFieldStub::build(),
            ),
        );

        self::assertEmpty($builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker));
        self::assertEmpty(
            $builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker)
        );
    }

    public function testHappyPath(): void
    {
        $first_field_id  = 123;
        $second_field_id = 124;
        $builder         = new ConfiguredFieldCollectionBuilder(
            RetrieveConfiguredFieldStub::withConfiguredFields(
                new ArtifactSectionField($first_field_id, DisplayType::COLUMN),
                new ArtifactSectionField($second_field_id, DisplayType::BLOCK),
            ),
            new SuitableFieldRetriever(
                RetrieveUsedFieldsStub::withFields(
                    StringFieldBuilder::aStringField($first_field_id)
                        ->withReadPermission($this->user, true)
                        ->inTracker($this->tracker)
                        ->build(),
                    StringFieldBuilder::aStringField($second_field_id)
                        ->withReadPermission($this->user, true)
                        ->inTracker($this->tracker)
                        ->build(),
                ),
                RetrieveSemanticDescriptionFieldStub::withNoField(),
                RetrieveSemanticTitleFieldStub::build(),
            )
        );

        $scenarios = [
            $builder->buildFromArtidoc($this->artidoc, $this->user)->getFields($this->tracker),
            $builder->buildFromSectionIdentifier($this->section_id, $this->user)->getFields($this->tracker),
        ];

        foreach ($scenarios as $configured_fields) {
            self::assertCount(2, $configured_fields);
            self::assertSame($first_field_id, $configured_fields[0]->field->getId());
            self::assertSame(DisplayType::COLUMN, $configured_fields[0]->display_type);
            self::assertSame($second_field_id, $configured_fields[1]->field->getId());
            self::assertSame(DisplayType::BLOCK, $configured_fields[1]->display_type);
        }
    }
}
