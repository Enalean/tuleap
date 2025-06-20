<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\REST\Artifact;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactCollectionFormat;
use Tuleap\Tracker\REST\Artifact\ArtifactCollectionFormatter;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueListFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\FlatArtifactListValueLabelArrayTransformer;
use Tuleap\Tracker\REST\Artifact\FlatArtifactListValueLabelFlatStringTransformer;
use Tuleap\Tracker\REST\Artifact\FlatArtifactRepresentationTransformer;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactCollectionFormatterTest extends TestCase
{
    private ArtifactCollectionFormatter $formatter;

    protected function setUp(): void
    {
        $html_purifier = $this->createStub(\Codendi_HTMLPurifier::class);
        $html_purifier->method('purify')->willReturnCallback(fn (string $value): string => $value);

        $field = $this->createStub(\Tracker_FormElement_Field::class);
        $field->method('getId')->willReturn(1);
        $field->method('getName')->willReturn('field_name');

        $fields_retriever = RetrieveUsedFieldsStub::withFields($field);
        $this->formatter  = new ArtifactCollectionFormatter(
            new FlatArtifactRepresentationTransformer($fields_retriever, $html_purifier, new FlatArtifactListValueLabelArrayTransformer()),
            new FlatArtifactRepresentationTransformer($fields_retriever, $html_purifier, new FlatArtifactListValueLabelFlatStringTransformer()),
        );
    }

    public function testAppliesNestedTransformation(): void
    {
        $artifact   = $this->buildArtifactRepresentation();
        $collection = $this->formatter->format(
            ArtifactCollectionFormat::NESTED,
            [$artifact],
            fn($representations): array => ['test' => $representations],
        );

        $this->assertSame(['test' => [$artifact]], $collection);
    }

    public function testDoesFlatTransformation(): void
    {
        $artifact   = $this->buildArtifactRepresentation();
        $collection = $this->formatter->format(
            ArtifactCollectionFormat::FLAT,
            [$artifact],
            fn($representations): array => $representations,
        );

        $this->assertSame([['field_name' => ['value1', 'value2']]], $collection);
    }

    public function testDoesFlatWithSemicolonArrayTransformation(): void
    {
        $artifact   = $this->buildArtifactRepresentation();
        $collection = $this->formatter->format(
            ArtifactCollectionFormat::FLAT_WITH_SEMICOLON_STRING_ARRAY,
            [$artifact],
            fn($representations): array => $representations,
        );

        $this->assertSame([['field_name' => 'value1;value2']], $collection);
    }

    private function buildArtifactRepresentation(): ArtifactRepresentation
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(777);
        $artifact->method('getAssignedTo')->willReturn([]);
        $artifact->method('getSubmittedByUser')->willReturn(UserTestBuilder::aUser()->build());
        $artifact->method('getXRef')->willReturn('foo #777');
        $artifact->method('getUri')->willReturn('/foo/777');
        $artifact->method('getSubmittedOn')->willReturn(1);
        $artifact->method('getLastUpdateDate')->willReturn(12);
        $artifact->method('isOpen')->willReturn(false);
        $artifact->method('getTitle')->willReturn('title');
        $tracker = $this->createStub(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $artifact->method('getTracker')->willReturn($tracker);

        $field_value = new ArtifactFieldValueListFullRepresentation();
        $field_value->build(1, 'msb', 'label', [['label' => 'value1'], ['label' => 'value2']], []);

        return ArtifactRepresentation::build(
            UserTestBuilder::aUser()->build(),
            $artifact,
            [$field_value],
            null,
            $this->createStub(TrackerRepresentation::class),
            StatusValueRepresentation::buildFromValues('value', null),
            ProvideUserAvatarUrlStub::build(),
        );
    }
}
